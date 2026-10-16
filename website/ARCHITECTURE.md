# Clean Architecture Structure

## Tổng quan

Dự án này tuân theo Clean Architecture với sự phân tách rõ ràng giữa các layer:

```
app/
├── Domain/              # Enterprise Business Rules
├── Application/         # Application Business Rules  
├── Infrastructure/      # Frameworks & Drivers
├── Presentation/        # Interface Adapters
└── Providers/          # Laravel Service Providers
```

## Chi tiết các tầng

### 1. Domain Layer (Core Business Logic)
**Không phụ thuộc vào bất kỳ layer nào**

- `Entities/` - Domain entities (User, Post, etc.)
- `ValueObjects/` - Immutable value objects (Email, Money, etc.)
- `Enums/` - Domain enumerations
- `Exceptions/` - Domain-specific exceptions

**Ví dụ:**
```php
// app/Domain/Identity/Entities/User.php
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly EmailAddress $email,
        public readonly UserStatus $status,
    ) {}
}
```

### 2. Application Layer (Use Cases)
**Phụ thuộc: Domain only**

- `UseCases/` - Application services implementing business workflows
- `Contracts/` - Interfaces (Repositories, Services)
- `DTOs/` - Data Transfer Objects
- `Exceptions/` - Application-specific exceptions

**Ví dụ:**
```php
// app/Application/Identity/UseCases/RegisterUser/RegisterUserUseCase.php
interface RegisterUserUseCase
{
    public function execute(string $name, string $email, string $password): void;
}

// app/Application/Identity/Contracts/UserRepository.php
interface UserRepository
{
    public function findByEmail(EmailAddress $email): ?User;
    public function create(string $name, EmailAddress $email, string $hash): User;
}
```

### 3. Infrastructure Layer (Implementation Details)
**Phụ thuộc: Domain, Application**

- `Persistence/Eloquent/` - Eloquent models (EloquentUser)
- `Identity/Persistence/` - Repository implementations
- `Mail/` - Email infrastructure (Mailable classes)
- `Security/` - Password hashing, URL generation
- `Authentication/` - Auth adapters (reserved)

**Ví dụ:**
```php
// app/Infrastructure/Persistence/Eloquent/EloquentUser.php
// Eloquent model - framework specific
class EloquentUser extends Authenticatable { }

// app/Infrastructure/Identity/Persistence/QueryBuilderUserRepository.php
// Repository implementation using Query Builder
final class QueryBuilderUserRepository implements UserRepository
{
    public function create(string $name, EmailAddress $email, string $hash): User
    {
        $id = DB::table('users')->insertGetId([...]);
        return new User($id, $name, $email, ...); // Return Domain entity
    }
}
```

### 4. Presentation Layer (Controllers & Views)
**Phụ thuộc: Application**

- `Web/Controllers/` - Web controllers
- `Web/Requests/` - Form request validators
- `Api/V1/` - API controllers (reserved)
- `Admin/` - Admin panel (reserved)
- `Views/` - Blade templates
- `Css/` - Stylesheets
- `Js/` - JavaScript

**Ví dụ:**
```php
// app/Presentation/Web/Controllers/RegisterUserController.php
final class RegisterUserController extends Controller
{
    public function __invoke(
        RegisterUserRequest $request,
        RegisterUserUseCase $registerUser,
    ): RedirectResponse {
        $data = $request->validated();
        
        try {
            $registerUser->execute(
                name: $data['name'],
                email: $data['email'],
                password: $data['password'],
            );
        } catch (EmailAlreadyRegistered) {
            throw ValidationException::withMessages([
                'email' => 'Email address is already registered.',
            ]);
        }
        
        return redirect()->route('verification.notice');
    }
}
```

## Dependency Flow

```
Presentation → Application → Domain
     ↓              ↓
Infrastructure ─────┘
```

**Quy tắc:**
- Domain không phụ thuộc vào bất kỳ layer nào
- Application chỉ phụ thuộc Domain
- Infrastructure implement các interface từ Application
- Presentation chỉ gọi Use Cases từ Application

## Laravel Integration

### Service Providers
Binding interfaces với implementations trong `IdentityServiceProvider`:

```php
// app/Providers/IdentityServiceProvider.php
$this->app->bind(UserRepository::class, QueryBuilderUserRepository::class);
$this->app->bind(RegisterUserUseCase::class, RegisterUserService::class);
$this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
```

### Authentication
Config sử dụng `EloquentUser` từ Infrastructure:

```php
// config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => EloquentUser::class, // Infrastructure model
    ],
],
```

### Routes
Routes reference Presentation controllers:

```php
// routes/web.php
use App\Presentation\Web\Controllers\RegisterUserController;

Route::post('/register', RegisterUserController::class);
```

## Benefits

1. **Testability** - Business logic (Domain + Application) có thể test độc lập
2. **Maintainability** - Mỗi layer có responsibility rõ ràng
3. **Framework Independence** - Có thể thay đổi framework mà không ảnh hưởng business logic
4. **Database Independence** - Repository pattern cho phép swap database
5. **Flexibility** - Dễ dàng thêm API, CLI, hoặc interface khác

## Migration từ MVC

**Đã xóa:**
- ❌ `app/Http/Controllers/` - Moved to `app/Presentation/Web/Controllers/`
- ❌ `app/Http/Requests/` - Moved to `app/Presentation/Web/Requests/`
- ❌ `app/Models/User.php` - Replaced by `app/Infrastructure/Persistence/Eloquent/EloquentUser.php`
- ❌ `app/Mail/` - Moved to `app/Infrastructure/Mail/`

**Structure mới:**
- ✅ Clean separation of concerns
- ✅ Dependency inversion principle
- ✅ Testable business logic
- ✅ Framework-agnostic core
