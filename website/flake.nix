{
  description = "Bút Mây blog platform - dev shell (PHP 8.4 + redis, Composer, Node 24)";

  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";

  outputs = { self, nixpkgs }:
    let
      systems = [ "x86_64-linux" "aarch64-linux" "x86_64-darwin" "aarch64-darwin" ];
      forAllSystems = f: nixpkgs.lib.genAttrs systems (system: f nixpkgs.legacyPackages.${system});
    in
    {
      devShells = forAllSystems (pkgs:
        let
          # The README requires: intl, pdo_mysql, mbstring, zip, redis.
          # nixpkgs' default PHP already enables the first four; add redis.
          php = pkgs.php84.buildEnv {
            extensions = { enabled, all }: enabled ++ [ all.redis ];
            extraConfig = ''
              memory_limit = 512M
            '';
          };
        in
        {
          default = pkgs.mkShell {
            packages = [
              php
              php.packages.composer
              pkgs.nodejs_24
            ];

            shellHook = ''
              echo "PHP $(php -r 'echo PHP_VERSION;') | Node $(node -v)"
              php -m | grep -qi '^redis$' || echo "WARNING: redis extension missing"
            '';
          };
        });
    };
}
