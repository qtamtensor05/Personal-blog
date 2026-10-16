<?php

declare(strict_types=1);

namespace App\Application\Domains\UseCases\ManageDomains;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Domains\Contracts\BlogDomainRepository;
use App\Application\Domains\Contracts\DnsTxtResolver;
use App\Application\Domains\DTOs\BlogDomainRecord;
use App\Domain\Domains\Enums\DomainStatus;
use DomainException;

/**
 * Custom domains of a blog (owner-only).
 *
 * Ownership is proven with a DNS TXT record. Only the SHA-256 hash of the token is stored
 * (blog_domains.verification_token_hash); the token itself is derived with an HMAC of the
 * app secret, so it can be shown to the owner again without ever being stored.
 */
final readonly class ManageDomainsService
{
    public const TXT_PREFIX = '_personal-blog-verify';

    public function __construct(
        private OwnedBlogGuard $guard,
        private BlogDomainRepository $domains,
        private DnsTxtResolver $dns,
        private string $secret,
        private string $platformHost,
    ) {}

    public function add(int $actorId, int $blogId, string $hostname): int
    {
        $this->guard->ensure($actorId, $blogId);
        $hostname = $this->normalize($hostname);

        if ($this->domains->hostnameExists($hostname)) {
            throw new DomainException('This domain is already connected to a blog.');
        }

        return $this->domains->create($blogId, $hostname, hash('sha256', $this->token($blogId, $hostname)));
    }

    public function verify(int $actorId, int $blogId, int $domainId): bool
    {
        $domain = $this->require($actorId, $blogId, $domainId);

        if (! in_array($domain->status, [DomainStatus::Pending, DomainStatus::Failed], true)) {
            throw new DomainException('This domain is already verified.');
        }

        $token = $this->token($blogId, $domain->hostname);
        $found = in_array($token, $this->dns->txtRecords($this->txtName($domain->hostname)), true);

        // The stored hash must also match, so a changed app key cannot verify an old record.
        $verified = $found && hash_equals($domain->verificationTokenHash, hash('sha256', $token));

        $this->domains->setStatus($blogId, $domainId, $verified ? DomainStatus::Verified : DomainStatus::Failed);

        return $verified;
    }

    public function activate(int $actorId, int $blogId, int $domainId): void
    {
        $domain = $this->require($actorId, $blogId, $domainId);

        // DISABLED domains were verified before, so they can be switched back on.
        if (! in_array($domain->status, [DomainStatus::Verified, DomainStatus::Disabled], true) || $domain->verifiedAt === null) {
            throw new DomainException('Verify the domain before activating it.');
        }

        $this->domains->setStatus($blogId, $domainId, DomainStatus::Active);
    }

    public function disable(int $actorId, int $blogId, int $domainId): void
    {
        $domain = $this->require($actorId, $blogId, $domainId);

        if ($domain->status !== DomainStatus::Active) {
            throw new DomainException('Only an active domain can be disabled.');
        }

        $this->domains->setStatus($blogId, $domainId, DomainStatus::Disabled);
    }

    public function remove(int $actorId, int $blogId, int $domainId): void
    {
        $this->require($actorId, $blogId, $domainId);
        $this->domains->delete($blogId, $domainId);
    }

    /**
     * DNS record the owner must create, for display.
     *
     * @return array{name: string, value: string}
     */
    public function instructions(BlogDomainRecord $domain): array
    {
        return [
            'name' => $this->txtName($domain->hostname),
            'value' => $this->token($domain->blogId, $domain->hostname),
        ];
    }

    private function require(int $actorId, int $blogId, int $domainId): BlogDomainRecord
    {
        $this->guard->ensure($actorId, $blogId);

        return $this->domains->find($blogId, $domainId) ?? throw new DomainException('Domain not found.');
    }

    private function token(int $blogId, string $hostname): string
    {
        return 'pbv-'.substr(hash_hmac('sha256', "blog-domain:{$blogId}:{$hostname}", $this->secret), 0, 40);
    }

    private function txtName(string $hostname): string
    {
        return self::TXT_PREFIX.'.'.$hostname;
    }

    private function normalize(string $hostname): string
    {
        $hostname = rtrim(strtolower(trim($hostname)), '.');

        $valid = strlen($hostname) <= 253
            && preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $hostname) === 1;

        if (! $valid) {
            throw new DomainException('Enter a domain name such as blog.example.com.');
        }

        // The platform's own host (and its subdomains) cannot be claimed by a blog.
        if ($hostname === $this->platformHost || str_ends_with($hostname, '.'.$this->platformHost)) {
            throw new DomainException('This domain belongs to the platform.');
        }

        return $hostname;
    }
}
