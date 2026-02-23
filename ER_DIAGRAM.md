# Entity Relationship Diagram (ERD)

Sơ đồ quan hệ thực thể cho hệ thống, mô tả các bảng chính và mối quan hệ giữa chúng.

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        string password
        string discord_id UK "Nullable"
        string discord_token "Nullable"
        string discord_refresh_token "Nullable"
        string discord_avatar "Nullable"
        string country "Nullable"
        string online_from "Nullable"
        string online_to "Nullable"
        string ingame_name "Nullable"
        string ingame_id "Nullable"
        bigint main_skill_id FK "Nullable"
        bigint sub_skill_id FK "Nullable"
        timestamp email_verified_at "Nullable"
        timestamp created_at
        timestamp updated_at
    }

    staffs {
        bigint id PK
        string name
        string account UK
        string email UK
        string password
        string role "default: admin"
        timestamp created_at
        timestamp updated_at
    }

    events {
        bigint id PK
        string title
        text description "Nullable"
        string type "default: casual"
        text rules "Nullable"
        text rewards "Nullable"
        datetime start_time
        datetime end_time "Nullable"
        string location "Nullable"
        string status "default: upcoming"
        json formation_data "Nullable"
        bigint created_by FK
        timestamp created_at
        timestamp updated_at
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
        string color "default: blue"
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

    user_inner_way {
        bigint id PK
        bigint user_id FK
        bigint inner_way_id FK
        integer level "default: 1"
        timestamp created_at
        timestamp updated_at
    }

    %% Relationships
    users ||--o| skills : "main_skill_id"
    users ||--o| skills : "sub_skill_id"
    
    users ||--o{ event_user : "participates"
    events ||--o{ event_user : "has participants"
    
    users ||--o{ user_inner_way : "practices"
    inner_ways ||--o{ user_inner_way : "practiced by"
    
    staffs ||--o{ events : "creates"
```

## Giải thích quan hệ

1. **Users & Skills**:
   - Một `User` có thể chọn một `main_skill` và một `sub_skill` từ bảng `skills`.
   - Quan hệ: Một-Nhiều (Skills -> Users), nhưng cụ thể là user giữ khóa ngoại.

2. **Users & InnerWays**:
   - Quan hệ Nhiều-Nhiều thông qua bảng trung gian `user_inner_way`.
   - Mỗi liên kết lưu thêm thuộc tính `level` (cấp độ nội công của user đó).

3. **Users & Events**:
   - Quan hệ Nhiều-Nhiều thông qua bảng trung gian `event_user`.
   - Bảng trung gian lưu thêm `preferred_time` (thời gian mong muốn tham gia của user).

4. **Staffs & Events**:
   - Một `Staff` (admin/master) tạo ra nhiều `Event`.
   - `Event` lưu `created_by` trỏ tới `staffs.id`.
