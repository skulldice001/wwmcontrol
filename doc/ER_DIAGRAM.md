# Entity Relationship Diagram (ERD)

Sơ đồ quan hệ thực thể cho hệ thống The Zoo.  
Các bảng được nhóm theo chức năng; sơ đồ chia thành nhiều nhóm để dễ đọc.

---

## 1. Core — Người dùng & Staff

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string account UK
        string email UK
        string password
        string discord_id "Nullable, indexed"
        string discord_token "Nullable"
        string discord_refresh_token "Nullable"
        string discord_avatar "Nullable"
        string avatar "Nullable"
        string country "Nullable"
        string online_from "Nullable"
        string online_to "Nullable"
        string ingame_name "Nullable"
        string ingame_id "Nullable"
        bigint main_skill_id FK "Nullable → skills"
        bigint sub_skill_id FK "Nullable → skills"
        ubigint z_coins "default 5000"
        ubigint z_coins_frozen "default 0"
        timestamp email_verified_at "Nullable"
        timestamp deleted_at "SoftDelete"
        timestamp created_at
        timestamp updated_at
    }

    staffs {
        bigint id PK
        string name
        string account UK
        string email UK
        string password
        string role "master|admin|observer|librarian"
        timestamp deleted_at "SoftDelete"
        timestamp created_at
        timestamp updated_at
    }

    theme_settings {
        bigint id PK
        bigint user_id FK
        boolean dark_mode "default false"
        string navbar_variant
        string sidebar_variant
        string brand_logo_variant "Nullable"
        string accent_color "Nullable"
        string background_color "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    sessions {
        string id PK
        bigint user_id FK "Nullable"
        string ip_address "Nullable"
        text user_agent "Nullable"
        longtext payload
        int last_activity "indexed"
    }

    personal_access_tokens {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        text name
        string token UK
        text abilities "Nullable"
        timestamp last_used_at "Nullable"
        timestamp expires_at "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    password_reset_tokens {
        string email PK
        string token
        timestamp created_at "Nullable"
    }

    skills {
        bigint id PK
        string name
        string slug UK
        string icon
        timestamp created_at
        timestamp updated_at
    }

    inner_ways {
        bigint id PK
        string name UK
        string slug UK
        string icon "Nullable"
        string color "default blue"
        timestamp created_at
        timestamp updated_at
    }

    user_inner_way {
        bigint id PK
        bigint user_id FK
        bigint inner_way_id FK
        integer level "default 0"
        timestamp created_at
        timestamp updated_at
    }

    users ||--o| skills : "main_skill_id"
    users ||--o| skills : "sub_skill_id"
    users ||--|| theme_settings : "1-1"
    users ||--o{ sessions : "has"
    users ||--o{ user_inner_way : "practices"
    inner_ways ||--o{ user_inner_way : "practiced by"
```

---

## 2. Events & Thư Viện

```mermaid
erDiagram
    staffs {
        bigint id PK
        string name
        string role
    }

    users {
        bigint id PK
        string name
    }

    events {
        bigint id PK
        string discord_id "Nullable"
        string title
        text description "Nullable"
        string type "default casual"
        text rules "Nullable"
        text rewards "Nullable"
        datetime start_time
        datetime end_time "Nullable"
        string location "Nullable"
        string status "default upcoming"
        json formation_data "Nullable"
        bigint created_by FK "→ staffs"
        timestamp deleted_at "SoftDelete"
        timestamp created_at
        timestamp updated_at
    }

    event_user {
        bigint id PK
        bigint event_id FK
        bigint user_id FK
        string preferred_time "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    library_articles {
        bigint id PK
        string title
        string category "indexed"
        text content
        string excerpt "Nullable, max 500"
        string status "draft|published, indexed"
        string discord_message_id "UK, Nullable"
        string discord_author "Nullable"
        bigint created_by FK "Nullable → staffs"
        timestamp published_at "Nullable"
        timestamp deleted_at "SoftDelete"
        timestamp created_at
        timestamp updated_at
    }

    staffs ||--o{ events : "creates"
    staffs ||--o{ library_articles : "authors"
    users ||--o{ event_user : "joins"
    events ||--o{ event_user : "has"
```

---

## 3. Tài chính — Zoo Coins

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        ubigint z_coins
        ubigint z_coins_frozen
    }

    staffs {
        bigint id PK
        string name
        string role
    }

    zoo_coin_transactions {
        bigint id PK
        bigint user_id FK "→ users"
        string type "daily_bonus|adjust|poker_win|..."
        ubigint amount
        ubigint balance_before
        ubigint balance_after
        string note "Nullable"
        bigint staff_id FK "Nullable → staffs"
        bigint related_user_id FK "Nullable → users"
        timestamp created_at
        timestamp updated_at
    }

    users ||--o{ zoo_coin_transactions : "owner"
    users ||--o{ zoo_coin_transactions : "related_user"
    staffs ||--o{ zoo_coin_transactions : "adjusted_by"
```

---

## 4. Entertainment — Poker

```mermaid
erDiagram
    users {
        bigint id PK
        string name
    }

    poker_tables {
        bigint id PK
        boolean is_ai_mode "default false"
        string name
        string type "default no_limit_holdem"
        decimal small_blind
        decimal big_blind
        decimal min_buy_in
        decimal max_buy_in
        int current_players "default 0"
        int max_players "default 9"
        string status "default waiting"
        timestamp created_at
        timestamp updated_at
    }

    poker_table_players {
        bigint id PK
        bigint poker_table_id FK
        bigint user_id FK "UNIQUE"
        boolean is_ready "default false"
        timestamp joined_at
    }

    poker_games {
        bigint id PK
        bigint poker_table_id FK
        json state
        timestamp created_at
        timestamp updated_at
    }

    poker_messages {
        bigint id PK
        bigint table_id FK
        bigint user_id FK
        string message "max 500"
        timestamp created_at
        timestamp updated_at
    }

    poker_tables ||--o{ poker_table_players : "has"
    users ||--o{ poker_table_players : "sits at"
    poker_tables ||--o{ poker_games : "runs"
    poker_tables ||--o{ poker_messages : "chat"
    users ||--o{ poker_messages : "sends"
```

---

## 5. Entertainment — Blackjack

```mermaid
erDiagram
    users {
        bigint id PK
        string name
    }

    blackjack_tables {
        bigint id PK
        boolean is_ai_mode "default false"
        string name
        ubigint min_bet "default 100"
        ubigint max_bet "default 10000"
        uint current_players "default 0"
        uint max_players "default 7"
        string status "default waiting"
        boolean is_preset "default false"
        timestamp created_at
        timestamp updated_at
    }

    blackjack_table_players {
        bigint id PK
        bigint blackjack_table_id FK
        bigint user_id FK "UNIQUE"
        string role "player|dealer"
        utinyint seat "Nullable"
        boolean is_ready "default false"
        timestamp joined_at "Nullable"
    }

    blackjack_games {
        bigint id PK
        bigint blackjack_table_id FK
        bigint user_id FK
        json state
        timestamp created_at
        timestamp updated_at
    }

    blackjack_rounds {
        bigint id PK
        bigint blackjack_table_id FK "indexed"
        string phase "default betting"
        json state
        ubigint current_turn_user_id "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    blackjack_messages {
        bigint id PK
        bigint table_id FK
        bigint user_id FK
        string message "max 500"
        timestamp created_at
        timestamp updated_at
    }

    blackjack_tables ||--o{ blackjack_table_players : "has"
    users ||--o{ blackjack_table_players : "sits at"
    blackjack_tables ||--o{ blackjack_games : "runs"
    blackjack_tables ||--o{ blackjack_rounds : "rounds"
    blackjack_tables ||--o{ blackjack_messages : "chat"
    users ||--o{ blackjack_messages : "sends"
```

---

## 6. Entertainment — Tài Xỉu & Bingo

```mermaid
erDiagram
    users {
        bigint id PK
        string name
    }

    taixiu_tables {
        bigint id PK
        string name "max 60"
        ubigint min_bet "default 100"
        ubigint max_bet "default 10000"
        uint max_players "default 20"
        uint current_players "default 0"
        string status "default waiting"
        timestamp created_at
        timestamp updated_at
    }

    taixiu_table_players {
        bigint id PK
        bigint taixiu_table_id FK "indexed"
        bigint user_id FK "UNIQUE"
        timestamp joined_at "Nullable"
    }

    taixiu_games {
        bigint id PK
        bigint taixiu_table_id FK "indexed"
        json state
        timestamp created_at
        timestamp updated_at
    }

    taixiu_messages {
        bigint id PK
        bigint table_id FK
        bigint user_id FK
        string message "max 500"
        timestamp created_at
        timestamp updated_at
    }

    bingo_tables {
        bigint id PK
        string name "max 60"
        string status "default waiting"
        utinyint current_players "default 0"
        utinyint max_players "default 9"
        utinyint min_players "default 3"
        ubigint entry_fee "default 50"
        timestamp created_at
        timestamp updated_at
    }

    bingo_table_players {
        bigint bingo_table_id PK
        bigint user_id PK
        boolean is_ready "default false"
        timestamp joined_at
    }

    bingo_games {
        bigint id PK
        bigint bingo_table_id FK "indexed"
        json state
        timestamp created_at
        timestamp updated_at
    }

    taixiu_tables ||--o{ taixiu_table_players : "has"
    users ||--o{ taixiu_table_players : "joins"
    taixiu_tables ||--o{ taixiu_games : "runs"
    taixiu_tables ||--o{ taixiu_messages : "chat"
    users ||--o{ taixiu_messages : "sends"

    bingo_tables ||--o{ bingo_table_players : "has"
    users ||--o{ bingo_table_players : "joins"
    bingo_tables ||--o{ bingo_games : "runs"
```

---

## 7. Entertainment — Tiến Lên

```mermaid
erDiagram
    users {
        bigint id PK
        string name
    }

    tienlen_tables {
        bigint id PK
        bigint owner_id FK "→ users"
        string name
        enum variant "mien_nam|mien_bac"
        uint entry_fee "default 100"
        enum status "waiting|playing|finished"
        boolean is_ai_mode "default false"
        timestamp created_at
        timestamp updated_at
    }

    tienlen_table_players {
        bigint id PK
        bigint tienlen_table_id FK
        bigint user_id FK
        utinyint seat "default 0"
        boolean is_ready "default false"
        timestamp joined_at
    }

    tienlen_games {
        bigint id PK
        bigint tienlen_table_id FK
        json state
        enum status "active|finished"
        timestamp created_at
        timestamp updated_at
    }

    tienlen_messages {
        bigint id PK
        bigint tienlen_table_id FK
        bigint user_id FK
        text body
        timestamp created_at
    }

    users ||--o{ tienlen_tables : "owns"
    tienlen_tables ||--o{ tienlen_table_players : "has"
    users ||--o{ tienlen_table_players : "sits at"
    tienlen_tables ||--o{ tienlen_games : "runs"
    tienlen_tables ||--o{ tienlen_messages : "chat"
    users ||--o{ tienlen_messages : "sends"
```

---

## 8. Lottery

```mermaid
erDiagram
    users {
        bigint id PK
        string name
    }

    lottery_draws {
        bigint id PK
        string type "daily|weekly|jackpot, indexed"
        string status "open|closed|drawn, indexed"
        timestamp draw_at "Nullable, indexed"
        timestamp opens_at "Nullable"
        timestamp drawn_at "Nullable"
        json winning_numbers "Nullable"
        usmallint pick_count
        usmallint multiplier
        ubigint ticket_price
        ubigint total_tickets
        ubigint total_pot
        ubigint total_payout
        timestamp created_at
        timestamp updated_at
    }

    lottery_tickets {
        bigint id PK
        bigint lottery_draw_id FK "indexed"
        bigint user_id FK "indexed"
        usmallint picked_number
        json picked_numbers "Nullable"
        ubigint bet_amount
        boolean is_winner "Nullable"
        ubigint payout "default 0"
        timestamp created_at
        timestamp updated_at
    }

    lottery_draws ||--o{ lottery_tickets : "has"
    users ||--o{ lottery_tickets : "buys"
```

---

## 9. Infrastructure (Queue, Cache, Jobs)

> Các bảng này được quản lý bởi Laravel framework, không cần chỉnh sửa thủ công.

| Bảng | Mục đích |
|------|----------|
| `jobs` | Queue jobs đang chờ xử lý |
| `job_batches` | Batch jobs (xử lý nhóm) |
| `failed_jobs` | Jobs thất bại, cần retry |
| `cache` | Cache key-value |
| `cache_locks` | Mutex locks cho cache |
| `sessions` | Lưu session người dùng (driver: database) |

---

## Tóm tắt quan hệ giữa các nhóm

```
users ─────────────────────────────────────────────────────────────────
  │  m:1 skills (main_skill, sub_skill)
  │  1:1 theme_settings
  │  m:n events           (qua event_user)
  │  m:n inner_ways       (qua user_inner_way)
  │  m:n poker_tables     (qua poker_table_players)
  │  m:n blackjack_tables (qua blackjack_table_players)
  │  m:n taixiu_tables    (qua taixiu_table_players)
  │  m:n bingo_tables     (qua bingo_table_players)
  │  m:n tienlen_tables   (qua tienlen_table_players)
  │  1:m zoo_coin_transactions
  └  1:m lottery_tickets

staffs ────────────────────────────────────────────────────────────────
  │  1:m events (created_by)
  │  1:m library_articles (created_by)
  └  1:m zoo_coin_transactions (staff_id)

Game tables (poker/blackjack/taixiu/bingo/tienlen) ────────────────────
  └  1:m *_games, *_messages, *_table_players (per-game pattern)

lottery_draws ─────────────────────────────────────────────────────────
  └  1:m lottery_tickets
```

---

## Thống kê schema

| Nhóm | Số bảng |
|------|---------|
| Core (users, staffs, auth) | 7 |
| User profile (skills, inner_ways) | 3 |
| Events & Library | 3 |
| Tài chính | 1 |
| Poker | 4 |
| Blackjack | 5 |
| Tài Xỉu | 4 |
| Bingo | 3 |
| Tiến Lên | 4 |
| Lottery | 2 |
| Infrastructure | 6 |
| **Tổng** | **42** |
