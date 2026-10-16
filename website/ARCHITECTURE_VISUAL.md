# Clean Architecture Layers - Visual Guide

## 🏗️ Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                      PRESENTATION LAYER                         │
│  (Interface Adapters - Converts data for use cases and views)  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📁 app/Presentation/                                           │
│  ├── Web/                                                       │
│  │   ├── Controllers/         # HTTP Controllers               │
│  │   │   ├── RegisterUserController.php                        │
│  │   │   ├── VerifyEmailController.php                         │
│  │   │   └── ResendVerificationEmailController.php             │
│  │   └── Requests/            # Form Validation                │
│  │       ├── RegisterUserRequest.php                            │
│  │       └── ResendVerificationEmailRequest.php                 │
│  ├── Api/V1/                  # API Controllers (reserved)      │
│  └── Views/                   # Blade Templates                 │
│                                                                 │
└────────────────────┬────────────────────────────────────────────┘
                     │ depends on ↓
┌─────────────────────────────────────────────────────────────────┐
│                      APPLICATION LAYER                          │
│     (Use Cases - Application business rules orchestration)      │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📁 app/Application/Identity/                                   │
│  ├── UseCases/                                                  │
│  │   ├── RegisterUser/                                          │
│  │   │   ├── RegisterUserUseCase.php      (Interface)          │
│  │   │   └── RegisterUserService.php      (Implementation)     │
│  │   ├── VerifyEmail/                                           │
│  │   └── ResendVerificationEmail/                               │
│  ├── Contracts/               # Interfaces                      │
│  │   ├── UserRepository.php                                     │
│  │   ├── PasswordHasher.php                                     │
│  │   └── VerificationEmailSender.php                            │
│  ├── DTOs/                    # Data Transfer Objects           │
│  └── Exceptions/              # Application exceptions          │
│                                                                 │
└────────────────────┬────────────────────────────────────────────┘
                     │ depends on ↓
┌─────────────────────────────────────────────────────────────────┐
│                         DOMAIN LAYER                            │
│         (Entities - Enterprise business rules core)             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📁 app/Domain/Identity/                                        │
│  ├── Entities/                                                  │
│  │   └── User.php             # Pure business entity            │
│  ├── ValueObjects/                                              │
│  │   └── EmailAddress.php     # Immutable value                 │
│  ├── Enums/                                                     │
│  │   └── UserStatus.php       # Domain enumerations             │
│  └── Exceptions/              # Domain exceptions               │
│                                                                 │
│  ⚠️  NO DEPENDENCIES - Pure PHP, Framework-agnostic             │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                     ↑ implements contracts
┌─────────────────────────────────────────────────────────────────┐
│                    INFRASTRUCTURE LAYER                         │
│   (Frameworks & Drivers - External concerns implementation)     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📁 app/Infrastructure/                                         │
│  ├── Persistence/Eloquent/                                      │
│  │   └── EloquentUser.php    # Laravel Eloquent Model          │
│  ├── Identity/Persistence/                                      │
│  │   └── QueryBuilderUserRepository.php  # Repository impl     │
│  ├── Mail/                                                      │
│  │   └── EmailVerificationMail.php       # Laravel Mailable    │
│  └── Security/                                                  │
│      ├── LaravelPasswordHasher.php                              │
│      └── LaravelVerificationUrlGenerator.php                    │
│                                                                 │
│  🔌 Implements Application Contracts                            │
│  🔌 Uses Domain Entities                                        │
│  🔌 Framework-specific code lives here                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## 🔄 Data Flow Example: User Registration

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. HTTP Request                                                 │
│    POST /register                                               │
│    { name, email, password }                                    │
└──────────────────┬──────────────────────────────────────────────┘
                   │
                   ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. PRESENTATION: RegisterUserController                         │
│    - Validates input via RegisterUserRequest                    │
│    - Calls use case                                             │
│                                                                 │
│    $registerUser->execute($name, $email, $password)             │
└──────────────────┬──────────────────────────────────────────────┘
                   │
                   ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. APPLICATION: RegisterUserService                             │
│    - Hash password via PasswordHasher                           │
│    - Create user via UserRepository                             │
│    - Send verification email                                    │
│                                                                 │
│    $user = $repository->create($name, $email, $hash)            │
└──────────────────┬──────────────────────────────────────────────┘
                   │
                   ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. INFRASTRUCTURE: QueryBuilderUserRepository                  │
│    - Insert into database via Query Builder                     │
│    - Return Domain Entity (not Eloquent model)                  │
│                                                                 │
│    $id = DB::table('users')->insertGetId([...])                 │
│    return new User($id, $name, $email, $status)  ← Domain!     │
└──────────────────┬──────────────────────────────────────────────┘
                   │
                   ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. DOMAIN: User Entity                                          │
│    - Pure PHP object                                            │
│    - Business rules encapsulated                                │
│    - No framework dependencies                                  │
│                                                                 │
│    return new User(                                             │
│        id: $id,                                                 │
│        name: $name,                                             │
│        email: EmailAddress,                                     │
│        status: UserStatus::PendingVerification                  │
│    )                                                            │
└─────────────────────────────────────────────────────────────────┘
```

## 🎯 Key Principles Illustrated

### ✅ Dependency Inversion Principle

```
❌ WRONG (Direct Dependency):
RegisterUserService → QueryBuilderUserRepository
   (Application)    →     (Infrastructure)

✅ RIGHT (Inverted via Interface):
RegisterUserService → UserRepository ← QueryBuilderUserRepository
   (Application)    →  (Interface)   ←   (Infrastructure)
```

### ✅ Repository Pattern

```
Controller asks for: Domain\User
Repository returns: Domain\User  (NOT Eloquent model!)

Infrastructure layer:
1. EloquentUser.php        ← For Laravel auth only
2. QueryBuilderUserRepository.php ← Creates Domain entities
```

### ✅ No Framework Leakage

```
Domain Layer:
├── User.php               ✅ Pure PHP
├── EmailAddress.php       ✅ Pure PHP
└── UserStatus.php         ✅ Pure PHP

❌ NO Eloquent
❌ NO Facades
❌ NO Laravel-specific code
```

## 📦 Package by Feature (Module Structure)

```
app/
├── Application/
│   └── Identity/          ← Feature module
│       ├── UseCases/
│       ├── Contracts/
│       └── DTOs/
├── Domain/
│   └── Identity/          ← Same feature module
│       ├── Entities/
│       └── ValueObjects/
├── Infrastructure/
│   └── Identity/          ← Same feature module
│       └── Persistence/
└── Presentation/
    └── Web/
        ├── Controllers/   ← Identity controllers here
        └── Requests/
```

Benefits:
- Related code stays together
- Easy to find files by feature
- Can extract module to microservice later

## 🧪 Testing Strategy

```
Unit Tests (Fast, Isolated):
└── Domain/Identity/Entities/UserTest.php
└── Application/Identity/UseCases/RegisterUserServiceTest.php
    Mock: UserRepository, PasswordHasher

Integration Tests (Database):
└── Infrastructure/Identity/Persistence/QueryBuilderUserRepositoryTest.php
    Real DB (SQLite in-memory)

Feature Tests (Full Stack):
└── Presentation/Web/Controllers/RegisterUserControllerTest.php
    Real HTTP requests
```

## 📝 Naming Conventions

```
Domain:
- User                     # Entity (noun)
- UserStatus              # Enum
- EmailAddress            # Value Object
- UserNotFound            # Exception

Application:
- RegisterUserUseCase     # Interface (verb + UseCase)
- RegisterUserService     # Implementation (verb + Service)
- UserRepository          # Contract (noun + Repository)

Infrastructure:
- QueryBuilderUserRepository      # Tech + Repository
- EloquentUser                    # Tech + Model name
- LaravelPasswordHasher           # Framework + Contract
- LaravelVerificationEmailSender  # Framework + Contract

Presentation:
- RegisterUserController  # Verb + Controller
- RegisterUserRequest     # Verb + Request
```

## 🚀 Extension Points

To add new features:

1. **Add Domain Entity**
   ```
   app/Domain/Blog/Entities/Post.php
   ```

2. **Add Use Case**
   ```
   app/Application/Blog/UseCases/PublishPost/
   ├── PublishPostUseCase.php
   └── PublishPostService.php
   ```

3. **Add Repository Contract**
   ```
   app/Application/Blog/Contracts/PostRepository.php
   ```

4. **Add Repository Implementation**
   ```
   app/Infrastructure/Blog/Persistence/QueryBuilderPostRepository.php
   ```

5. **Add Controller**
   ```
   app/Presentation/Web/Controllers/PublishPostController.php
   ```

6. **Bind in ServiceProvider**
   ```php
   $this->app->bind(PostRepository::class, QueryBuilderPostRepository::class);
   $this->app->bind(PublishPostUseCase::class, PublishPostService::class);
   ```

## 🎯 Result

✅ Clean separation of concerns
✅ Testable business logic
✅ Framework independence
✅ Easy to understand and maintain
✅ Scalable architecture
