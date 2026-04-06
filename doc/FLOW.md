# Luồng Chạy Code — WWMControl (The Zoo)

> Mô tả luồng xử lý từ lúc trình duyệt gửi request đến khi nhận response, bao gồm các lớp middleware, routing, game events và background jobs.

---

## 1. Tổng quan kiến trúc

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT (Browser)                         │
│              HTTP/HTTPS + WebSocket (wss://)                    │
└────────────────┬───────────────────────────┬────────────────────┘
                 │ HTTP                       │ WebSocket
                 ▼                            ▼
┌────────────────────────┐      ┌─────────────────────────┐
│   Nginx (host)         │      │  Laravel Reverb          │
│   SSL termination      │      │  port 8080               │
│   → proxy :8000        │      │  channels.php auth       │
└────────────┬───────────┘      └────────────┬────────────┘
             │                               │ broadcast()
             ▼                               │
┌────────────────────────┐                  │
│   public/index.php     │◄─────────────────┘
│   (entry point)        │
└────────────┬───────────┘
             │
             ▼
┌────────────────────────┐
│   bootstrap/app.php    │   configure routes, middleware,
│   Application::create()│   exceptions, schedule
└────────────┬───────────┘
             │
             ▼
┌────────────────────────┐
│   Middleware Stack     │   (xem mục 3)
└────────────┬───────────┘
             │
             ▼
┌────────────────────────┐
│   Router               │   web.php / api.php
│   (match route)        │
└────────────┬───────────┘
             │
             ▼
┌────────────────────────┐
│   Controller Action    │   gọi Model / Service
└────────────┬───────────┘
             │
      ┌──────┴──────┐
      ▼             ▼
┌──────────┐  ┌──────────────┐
│ Response │  │  Queue Job   │   dispatch → Redis/DB queue
│ View/JSON│  │  (async)     │
└──────────┘  └──────────────┘
```

---

## 2. Entry Point — `public/index.php`

```
1. define LARAVEL_START        // đánh dấu thời điểm bắt đầu
2. check maintenance mode      // storage/framework/maintenance.php
   └─ nếu đang maintenance → trả 503 ngay, không vào app
3. require vendor/autoload.php // Composer PSR-4 autoload
4. require bootstrap/app.php   // tạo Application instance
5. $app->handleRequest(        // bắt đầu xử lý request
       Request::capture()
   )
```

---

## 3. Bootstrap — `bootstrap/app.php`

```
Application::configure()
  │
  ├── withRouting()
  │     ├── web      → routes/web.php
  │     ├── api      → routes/api.php   (prefix: /api)
  │     ├── commands → routes/console.php
  │     ├── channels → routes/channels.php  (WebSocket auth)
  │     └── health   → GET /up  (health check endpoint)
  │
  ├── withMiddleware()
  │     ├── web group append: SetLocale
  │     ├── trustProxies(at: '*')     ← quan trọng khi đứng sau Nginx
  │     ├── statefulApi()             ← Sanctum cookie auth cho SPA
  │     ├── validateCsrfTokens(
  │     │       except: api/admin/login
  │     │   )
  │     ├── redirectGuestsTo()
  │     │     ├── admin/* → route('admin.login')
  │     │     └── khác   → route('login')
  │     └── redirectUsersTo('/dashboard')
  │
  ├── withExceptions()
  │     ├── TokenMismatchException → redirect login + flash error
  │     └── AccessDeniedHttpException → redirect / + flash error
  │
  └── withSchedule()  (xem mục 7)
```

---

## 4. Middleware Stack

Request đi qua các lớp theo thứ tự sau:

```
Request
  │
  ▼
[Global]
  ├── TrustProxies          // trust X-Forwarded-* từ Nginx
  ├── HandleCors            // CORS headers
  └── PreventRequestsDuringMaintenance
  │
  ▼
[Web Group]
  ├── EncryptCookies
  ├── AddQueuedCookiesToResponse
  ├── StartSession          // load session từ DB/Redis
  ├── ShareErrorsFromSession
  ├── VerifyCsrfToken       // bỏ qua: api/admin/login
  ├── SubstituteBindings    // route model binding
  └── SetLocale             // đọc session 'locale' → App::setLocale()
  │
  ▼
[Route Middleware]
  ├── auth          → guards: 'web' (User model)
  ├── auth:staff    → guards: 'staff' (Staff model)
  └── guest         → chặn nếu đã login
  │
  ▼
Controller
```

**Guards:**
| Guard | Model | Provider | Session key |
|-------|-------|----------|-------------|
| `web` | `User` | users table | `login_web_*` |
| `staff` | `Staff` | staff table | `login_staff_*` |

---

## 5. Routing — Sơ đồ các nhóm route

```
routes/web.php
│
├── GET  /                         → WelcomeController (trang chủ)
├── GET  /lang/{locale}            → ngôn ngữ (en / vi)
│
├── [guest] /login                 → Auth\LoginController
├── [guest] /admin/login           → Auth\AdminLoginController
│
├── [auth] /dashboard              → User routes
│    ├── /profile                  → ProfileController
│    ├── /events                   → UserEventController
│    ├── /zoo-coins                → ZooCoinController
│    ├── /library/{category}       → LibraryController
│    │
│    └── /entertainment
│         ├── /poker/*             → PokerGameController
│         ├── /blackjack/*         → BlackjackController
│         ├── /taixiu/*            → TaixiuController
│         ├── /bingo/*             → BingoController
│         └── /tienlen/*           → TienLenController
│         └── /lottery/*           → LotteryController
│
└── [auth:staff] /admin
     ├── /dashboard                → AdminController
     ├── /staff                    → StaffController (CRUD)
     ├── /events                   → Admin\EventController (CRUD + participants)
     ├── /users                    → Admin\UserController (CRUD + coins)
     └── /library                  → Admin\LibraryArticleController (CRUD + publish)

routes/api.php
├── [auth:sanctum]
│    ├── GET  /user                 → trả User + skills + inner ways
│    ├── GET  /skills               → danh sách kỹ năng
│    ├── POST /user/profile         → cập nhật profile
│    ├── POST /user/inner-ways      → cập nhật inner ways
│    ├── GET  /discord/check-role
│    └── GET  /events
└── [auth:staff]
     └── GET  /admin/user          → trả Staff object
```

---

## 6. Luồng xử lý Game (Real-time)

Các game (Poker, Blackjack, Tài Xỉu, Bingo, Tiến Lên) có luồng đặc biệt:

```
Client action (click nút)
       │
       ▼
POST /entertainment/{game}/action
       │
       ▼
GameController::action()
  ├── validate input
  ├── load Table + Game model (DB)
  ├── update game state (JSON column)
  ├── save to DB
  ├── dispatch Queue Job (nếu cần timeout/auto)
  │     └── e.g. AutoFoldJob, TaixiuAutoRollJob, BingoCallJob
  └── broadcast Event
        └── e.g. PokerTableUpdated, TaixiuTableUpdated
               │
               ▼
        Laravel Reverb (port 8080)
               │
               ▼
        Tất cả clients đang ở bàn đó
        nhận state mới qua WebSocket
```

**Events broadcast:**
| Game | Event | Channel |
|------|-------|---------|
| Poker | `PokerTableUpdated`, `PokerRoomUpdated` | private channel |
| Blackjack | `BlackjackTableUpdated`, `BlackjackRoomUpdated` | private channel |
| Tài Xỉu | `TaixiuTableUpdated`, `TaixiuRoomUpdated` | private channel |
| Bingo | `BingoRoomUpdated` | private channel |
| Tiến Lên | `TienLenTableUpdated` | private channel |

**WebSocket auth flow (`routes/channels.php`):**
```
Client kết nối wss://thezootopia.online/app/{key}
  → Reverb xác thực qua POST /broadcasting/auth
  → Middleware: auth:sanctum
  → channels.php: App.Models.User.{id}
      → return $user->id === (int) $id
```

---

## 7. Background Jobs & Scheduler

### Queue Workers

```
Job dispatch (từ Controller)
       │
       ▼
Queue (Redis driver trên Docker / database trên bare server)
       │
       ▼
Queue Worker Container / Supervisor process
  php artisan queue:work redis --tries=3 --timeout=90
       │
       ├── AutoFoldJob          → tự fold player timeout (Poker)
       ├── TaixiuAutoRollJob    → tự lắc xúc xắc (Tài Xỉu)
       ├── TaixiuAutoNextJob    → tự sang vòng mới (Tài Xỉu)
       ├── BingoCallJob         → tự gọi số (Bingo)
       └── TienLenAutoPassJob   → tự pass player timeout (Tiến Lên)
```

### Scheduler

Chạy mỗi phút qua container `scheduler` (`php artisan schedule:work`):

```
00:00 daily  → events:close-expired    // đóng event đã quá hạn
00:05 daily  → zoo:daily-bonus         // thưởng Zoo Coin hàng ngày
08:30 daily  → lottery:draw jackpot   // quay số jackpot
20:00 daily  → lottery:draw daily     // quay số hàng ngày
21:00 Sat    → lottery:draw weekly    // quay số hàng tuần
```

**Lottery draw flow:**
```
lottery:draw {type}
  ├── tìm LotteryDraw open có draw_at <= now()
  ├── lấy tất cả LotteryTicket của draw này
  ├── random chọn số trúng
  ├── tính winners + prize
  ├── cộng ZooCoin cho winners
  ├── đánh dấu draw = 'settled'
  └── createNext() → tạo LotteryDraw tiếp theo
        └── opens_at = now() (mở bán ngay)
```

---

## 8. Luồng Authentication

### User (web guard)

```
POST /login
  └── Auth\LoginController
        ├── validate email + password
        ├── Auth::attempt(['email', 'password'])
        │     └── bcrypt verify → users table
        ├── session regenerate
        └── redirect /dashboard
```

### Staff/Admin (staff guard)

```
POST /admin/login
  └── Auth\AdminLoginController
        ├── validate username + password
        ├── Auth::guard('staff')->attempt()
        │     └── bcrypt verify → staff table
        └── redirect /admin/dashboard
```

### Discord OAuth

```
GET /auth/discord/redirect
  └── Socialite::driver('discord')->redirect()

GET /auth/discord/callback
  └── DiscordController::callback()
        ├── Socialite::driver('discord')->user()
        ├── tìm hoặc tạo User theo discord_id
        ├── Auth::login($user)
        └── redirect /dashboard
```

---

## 9. Database Access

Mọi truy vấn đi qua **Eloquent ORM** → **PDO** → **PostgreSQL**:

```
Controller
  └── Eloquent Model (extends Model)
        ├── boot() / static methods
        ├── relationships (hasMany, belongsTo, ...)
        ├── scopes
        └── PDO → PostgreSQL (host: 61.14.234.57 / host.docker.internal)
                              DB: the_zoo_v2
```

**Các Model chính:**

| Nhóm | Models |
|------|--------|
| Auth | `User`, `Staff` |
| Profile | `ThemeSetting`, `Skill`, `InnerWay` |
| Events | `Event` |
| Games | `PokerTable`, `PokerGame`, `PokerMessage` |
| | `BlackjackTable`, `BlackjackGame`, `BlackjackRound`, `BlackjackMessage` |
| | `TaixiuTable`, `TaixiuGame`, `TaixiuMessage` |
| | `BingoTable`, `BingoGame` |
| | `TienLenTable`, `TienLenGame`, `TienLenTablePlayer`, `TienLenMessage` |
| Economy | `ZooCoinTransaction`, `LotteryTicket`, `LotteryDraw` |
| Content | `LibraryArticle` |

---

## 10. Session, Cache & Queue Drivers

| Service | Driver (bare server) | Driver (Docker) |
|---------|---------------------|-----------------|
| Session | `database` | `redis` |
| Cache | `database` | `redis` |
| Queue | `database` | `redis` |

**Bare server:** Sessions/cache/jobs lưu trong PostgreSQL tables (`sessions`, `cache`, `jobs`).

**Docker:** Sessions/cache/jobs lưu trong Redis container — nhanh hơn, không tốn DB I/O.

---

## 11. Response Flow

```
Controller return
  │
  ├── View response:  view('blade.template', $data)
  │     └── Blade compiler → PHP → HTML
  │           └── layouts/app.blade.php (user)
  │           └── layouts/admin.blade.php (staff)
  │
  └── JSON response:  response()->json($data)
        └── Content-Type: application/json
              (dùng cho game state polling / API endpoints)
```

---

## 12. Error Handling

```
Exception thrown
  │
  └── bootstrap/app.php withExceptions()
        ├── TokenMismatchException (CSRF expired)
        │     └── redirect login + "Phiên làm việc đã hết hạn"
        │
        ├── AccessDeniedHttpException (403)
        │     └── redirect / + "Không có quyền truy cập"
        │
        └── Mọi exception khác
              └── Laravel default handler
                    ├── APP_DEBUG=true  → Ignition debug page
                    └── APP_DEBUG=false → 500 / 404 error view
```

---

## 13. Infrastructure Stack (Docker)

```
thezootopia.online (HTTPS 443)
       │
       ▼
[Host] Nginx  ──────────────────────────────────────┐
  proxy_pass :8000                                   │ /storage/ served
  wss proxy  :8080                                   │ from host filesystem
       │                                             │
       ▼                                             │
[Docker network: app-network]                        │
  ┌──────────────────────────────────────────┐       │
  │  app       (Nginx:80 + PHP-FPM)  :8000  │       │
  │  reverb    (artisan reverb:start) :8080  │       │
  │  queue     (artisan queue:work)          │       │
  │  scheduler (artisan schedule:work)       │       │
  │  redis     (Redis Alpine)         :6379  │       │
  └──────────────────────────────────────────┘       │
                    │                                │
                    ▼                                │
         [Host] PostgreSQL :5432                     │
                 DB: the_zoo_v2          ◄───────────┘
         /var/www/wwmcontrol/storage/
```
