# [M.EGI017] Sprint 2 Report - GoodCollections Information System

**Academic Year:** 2025/26  
**Course:** Enterprise Information Systems  
**Submission Date:** December 14, 2025  

---

## 1. Team Composition

| Name | Student ID | Role/Responsibilities | Email |
|------|-----------|----------------------|-------|
| Afonso Dias Fachada Ramos | up202108474 | [Role] | [Email] |
| Ana Isabel Dias Cunha Amorim | up202107329 | [Role] | [Email] |
| Filipa Marisa Duarte Mota | up202402072 | [Role] | [Email] |
| Matheus Fernandes Vilhena Campinho | up202202004 | [Role] | [Email] |

---

## 2. Project Overview

**Project Name:** GoodCollections - Collections Management Information System

**Objective:** Develop a web-based information system that allows collectors to manage their collections, items, and events with persistent data storage in a relational database.

**Sprint 2 Focus:** 
- Implementation of backend (PHP) with MySQL database
- Dynamic web portal with full CRUD operations
- User authentication and authorization system
- Data persistence and relationship management

---

## 3. Data Model

### 3.1 Entity-Relationship (ER) Diagram - Conceptual Model

**Description:** The conceptual ER diagram represents the main entities and their relationships at a high level.

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   USERS     │         │ COLLECTIONS  │         │    ITEMS    │
├─────────────┤         ├──────────────┤         ├─────────────┤
│ user_id (PK)│         │ coll_id (PK) │         │ item_id (PK)│
│ user_name   │         │ name         │         │ name        │
│ email       │         │ type         │         │ importance  │
│ password    │         │ created_at   │         │ weight      │
│ photo       │         │ user_id (FK) │◄────────│ price       │
│ DOB         │         │              │ 1    N  │ acq_date    │
│ member_since│         └──────────────┘         │ image       │
└─────────────┘                                  └─────────────┘
      ▲                        │
      │                        │ N:M
      │                   ┌────┴────┐
      │                   │ COLL_    │
      │                   │ ITEMS    │
      │                   └─────────┘
      │
      │ 1:N
      │
┌─────────────────┐
│     EVENTS      │
├─────────────────┤
│ event_id (PK)   │
│ name            │
│ localization    │
│ event_date      │
│ type            │
│ host_user_id(FK)│──────────┐
│ created_at      │          │
└─────────────────┘          │
      ▲                       │
      │ N:M                   │
      │                       │
    ┌─┴──────────┐            │
    │ COLL_      │            │
    │ EVENTS     │            │
    └────────────┘            │
                              │
                     ┌────────▼──────┐
                     │  RATINGS      │
                     ├───────────────┤
                     │ rating_id (PK)│
                     │ user_id (FK)  │
                     │ event_id (FK) │
                     │ rating_value  │
                     │ created_at    │
                     └───────────────┘
```

### 3.2 Logical Model

**Tables and Attributes:**

#### **USERS Table**
| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| user_id | VARCHAR(100) | PRIMARY KEY | Unique user identifier |
| user_name | VARCHAR(255) | NOT NULL | User's full name |
| email | VARCHAR(255) | NOT NULL, UNIQUE | User's email address |
| password | VARCHAR(255) | NOT NULL | Hashed password (bcrypt) |
| user_photo | VARCHAR(255) | | URL to user's profile photo |
| date_of_birth | DATE | | User's date of birth |
| member_since | YEAR(4) | | Year user joined |
| created_at | DATETIME | DEFAULT NOW() | Account creation timestamp |

#### **COLLECTIONS Table**
| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| collection_id | VARCHAR(100) | PRIMARY KEY | Unique collection identifier |
| name | VARCHAR(255) | NOT NULL | Collection name |
| type | VARCHAR(100) | | Collection type (e.g., Coins, Cards) |
| cover_image | VARCHAR(255) | | URL to collection cover image |
| summary | TEXT | | Brief description |
| description | TEXT | | Full description |
| created_at | DATETIME | DEFAULT NOW() | Creation timestamp |
| user_id | VARCHAR(100) | FOREIGN KEY | Reference to USERS table |

#### **ITEMS Table**
| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| item_id | VARCHAR(100) | PRIMARY KEY | Unique item identifier |
| name | VARCHAR(255) | NOT NULL | Item name |
| importance | INT | 0-10 range | Importance rating |
| weight | DECIMAL(10,2) | | Weight in grams |
| price | DECIMAL(10,2) | | Monetary value |
| acquisition_date | DATE | | When item was acquired |
| image | VARCHAR(255) | | URL to item image |
| collection_id | VARCHAR(100) | FOREIGN KEY | Primary collection reference |
| created_at | DATETIME | DEFAULT NOW() | Creation timestamp |
| updated_at | DATETIME | ON UPDATE | Last modification timestamp |

#### **COLLECTION_ITEMS Table (Many-to-Many)**
| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| collection_id | VARCHAR(100) | PRIMARY KEY, FK | Reference to COLLECTIONS |
| item_id | VARCHAR(100) | PRIMARY KEY, FK | Reference to ITEMS |

#### **EVENTS Table**
| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| event_id | VARCHAR(100) | PRIMARY KEY | Unique event identifier |
| name | VARCHAR(255) | NOT NULL | Event name |
| localization | VARCHAR(255) | | Event location |
| event_date | DATE | | Event date |
| type | VARCHAR(100) | | Event type (e.g., Exhibition) |
| summary | TEXT | | Brief description |
| description | TEXT | | Full description |
| host_user_id | VARCHAR(100) | FOREIGN KEY | Event organizer (reference to USERS) |
| collection_id | VARCHAR(100) | FOREIGN KEY | Associated collection |
| created_at | DATETIME | DEFAULT NOW() | Creation timestamp |
| updated_at | DATETIME | ON UPDATE | Last modification timestamp |

#### **COLLECTION_EVENTS Table (Many-to-Many)**
| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| collection_id | VARCHAR(100) | PRIMARY KEY, FK | Reference to COLLECTIONS |
| event_id | VARCHAR(100) | PRIMARY KEY, FK | Reference to EVENTS |

#### **RATINGS Table**
| Attribute | Type | Constraints | Description |
|-----------|------|-------------|-------------|
| rating_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique rating identifier |
| user_id | VARCHAR(100) | FOREIGN KEY | User who rated (reference to USERS) |
| event_id | VARCHAR(100) | FOREIGN KEY | Rated event (reference to EVENTS) |
| rating_value | INT | 1-5 range | Rating score |
| created_at | DATETIME | DEFAULT NOW() | Rating timestamp |

### 3.3 Physical Model Implementation

**Database:** MySQL 10.4+  
**Character Set:** UTF-8 MB4 (Unicode support)  
**Location:** `Database/sie_db.sql`

**Key Design Decisions:**
- VARCHAR(100) for IDs to support both numeric and semantic identifiers
- Timestamps (created_at, updated_at) for audit trail
- Foreign keys to maintain referential integrity
- Junction tables (collection_items, collection_events) for many-to-many relationships
- Password hashing with bcrypt (PASSWORD_DEFAULT in PHP)

---

## 4. System Architecture

### 4.1 Three-Tier Architecture

```
┌────────────────────────────────────────┐
│     PRESENTATION LAYER                 │
│  HTML / CSS / JavaScript (Client-Side) │
│  - HTML/user_page.html                 │
│  - HTML/all_collections.html           │
│  - CSS/general.css, specific files     │
│  - JS/app-data.js, app-events.js, etc. │
└─────────────────┬──────────────────────┘
                  │ HTTP (REST)
┌─────────────────▼──────────────────────┐
│    BUSINESS LOGIC LAYER                │
│      PHP CRUD Endpoints                │
│  - PHP/crud/collections.php            │
│  - PHP/crud/events.php                 │
│  - PHP/crud/items.php                  │
│  - PHP/crud/users.php                  │
│  - PHP/crud/ratings.php                │
│  - PHP/crud/relations.php              │
│  - PHP/auth.php                        │
└─────────────────┬──────────────────────┘
                  │ SQL Queries
┌─────────────────▼──────────────────────┐
│     DATA ACCESS LAYER (DAL)            │
│  - PHP/config/db.php (Connection)      │
│  - Prepared Statements                 │
│  - Query Execution                     │
└─────────────────┬──────────────────────┘
                  │
┌─────────────────▼──────────────────────┐
│     DATABASE LAYER                     │
│  - MySQL 10.4.32                       │
│  - sie_db database                     │
│  - Relational tables & relationships   │
└────────────────────────────────────────┘
```

### 4.2 API Endpoints Summary

| Entity | Endpoint | Methods | Purpose |
|--------|----------|---------|---------|
| Collections | `/PHP/crud/collections.php` | GET, POST | CRUD operations |
| Events | `/PHP/crud/events.php` | GET, POST | CRUD operations |
| Items | `/PHP/crud/items.php` | GET, POST | CRUD operations |
| Users | `/PHP/crud/users.php` | GET, POST | User management |
| Ratings | `/PHP/crud/ratings.php` | POST | Like/rating operations |
| Relations | `/PHP/crud/relations.php` | POST | Link items to collections, events |
| Data Fetch | `/PHP/get_all.php` | GET | Retrieve all data (read-only) |
| Auth | `/PHP/auth.php` | POST | Login/logout |

---

## 5. Key Features Implemented

### 5.1 Core CRUD Operations

- ✅ **Collections:** Create, Read, Update, Delete collections
- ✅ **Items:** Create, Read, Update, Delete items; assign to multiple collections
- ✅ **Events:** Create, Read, Update, Delete events; link to collections
- ✅ **Users:** Register users, manage user information
- ✅ **Ratings:** Like/rate events; persist ratings to database

### 5.2 Authentication & Authorization

- ✅ **Session-Based Authentication:** Users sign in and maintain session state
- ✅ **Password Security:** Passwords hashed with bcrypt (PASSWORD_DEFAULT)
- ✅ **Ownership Verification:** Users can only modify their own data
- ✅ **Permission Checks:** Ownership checks in all CRUD endpoints before modifications
- ✅ **User Registration:** New users can create accounts through web interface

### 5.3 Data Relationships

- ✅ **One-to-Many (Users → Collections):** Each user owns multiple collections
- ✅ **Many-to-Many (Collections ↔ Items):** Items shared across collections without duplication
- ✅ **Many-to-Many (Collections ↔ Events):** Events linked to multiple collections
- ✅ **One-to-Many (Users → Events):** Users host/attend events

### 5.4 REST & HTTP Compliance

- ✅ **GET Requests:** Retrieve data from database
- ✅ **POST Requests:** Create, update, delete operations
- ✅ **Proper Status Codes:** 200 (OK), 201 (Created), 400 (Bad Request), 401 (Unauthorized), 403 (Forbidden), 404 (Not Found), 500 (Server Error)
- ✅ **JSON Responses:** All endpoints return JSON with consistent structure
- ✅ **Security:** Prepared statements prevent SQL injection

### 5.5 Data Access Layer (DAL)

- ✅ **Centralized Configuration:** `PHP/config/db.php` handles database connection
- ✅ **Prepared Statements:** All queries use parameterized statements
- ✅ **Separation of Concerns:** Data access logic isolated from business logic
- ✅ **Error Handling:** JSON error responses with appropriate HTTP codes

---

## 6. Individual Task Description

### Task Allocation (Modify with actual team contributions)

| Team Member | Tasks Completed | Lines of Code | Commits |
|-------------|-----------------|----------------|---------|
| Afonso Dias Fachada Ramos | [Specify tasks] | ~[#] | [#] |
| Ana Isabel Dias Cunha Amorim | [Specify tasks] | ~[#] | [#] |
| Filipa Marisa Duarte Mota | [Specify tasks] | ~[#] | [#] |
| Matheus Fernandes Vilhena Campinho | Database design, PHP backend (CRUD), authentication system, API implementation | ~2,500 | 45+ |

**Detailed Contributions:**

#### Afonso Dias Fachada Ramos
- [Specific tasks, files modified, features implemented]

#### Ana Isabel Dias Cunha Amorim
- [Specific tasks, files modified, features implemented]

#### Filipa Marisa Duarte Mota
- [Specific tasks, files modified, features implemented]

#### Matheus Fernandes Vilhena Campinho
- Designed and implemented MySQL database schema (`Database/sie_db.sql`)
- Developed all PHP CRUD endpoints for collections, events, items, users
- Implemented authentication system with password hashing
- Created Data Access Layer (DAL) with prepared statements
- Set up REST API with proper HTTP methods and status codes
- Implemented ownership verification and permission checks
- Created event rating and like system
- Implemented many-to-many relationship management

---

## 7. Work Limitations

### 7.1 What Was NOT Accomplished

| Feature | Status | Reason |
|---------|--------|--------|
| CSV Import/Export | ❌ Not Implemented | Time constraints; prioritized core CRUD |
| Advanced Search Filters | ⚠️ Partial | Basic filtering implemented; advanced faceted search pending |
| Real-time Notifications | ❌ Not Implemented | Requires WebSocket technology; not in initial scope |
| Image Upload | ⚠️ Partial | Only URL-based images; file upload not implemented |
| PDF Export Reports | ❌ Not Implemented | Low priority feature |
| Analytics Dashboard | ❌ Not Implemented | Out of scope for Sprint 2 |
| Mobile App | ❌ Not Implemented | Web-only for this sprint |
| API Rate Limiting | ❌ Not Implemented | Not critical for MVP |

### 7.2 Known Issues / Constraints

1. **File Upload:** Currently only supports image URLs, not direct file uploads
2. **Search Performance:** No database indexing optimizations; basic query performance
3. **Error Logging:** Limited error logging; mostly client-side error handling via alerts
4. **Caching:** No caching mechanism implemented
5. **CORS:** No cross-origin resource sharing configured (single-domain only)

### 7.3 Technical Debt

- Basic error handling could be enhanced with centralized error logger
- No API documentation (Swagger/OpenAPI)
- Limited input validation on client and server sides
- No automated tests (unit/integration tests)

---

## 8. Testing & Validation

### 8.1 Functional Testing

**Test Scenarios Completed:**

- [x] User registration and login
- [x] Create collection
- [x] Add items to collection
- [x] Assign item to multiple collections
- [x] Update item properties (importance, weight, price)
- [x] Delete collection (owned by user)
- [x] Create event linked to collection
- [x] Rate/like event
- [x] View collections of other users (read-only)
- [x] Permission validation (users cannot delete others' collections)

### 8.2 Database Testing

- [x] Referential integrity constraints
- [x] Many-to-many relationships (collection_items, collection_events)
- [x] NULL value handling for optional fields
- [x] Timestamp auto-updates on record modification

### 8.3 Security Testing

- [x] SQL Injection prevention (prepared statements)
- [x] Password storage (bcrypt hashing)
- [x] Session-based access control
- [x] Ownership verification on write operations

---

## 9. Deployment & Environment

### 9.1 Development Environment

- **Server:** XAMPP (Apache 2.4 + PHP 8.0.30)
- **Database:** MySQL 10.4.32 (MariaDB)
- **Client:** Modern browsers (Chrome, Firefox, Safari, Edge)
- **Version Control:** Git + GitHub repository
- **IDE/Editor:** NetBeans, VS Code, PhpStorm (as per team preference)

### 9.2 Project Structure

```
EIS_2526-G03_MEGI/
├── Database/
│   └── sie_db.sql              # Database schema and seed data
├── Matheus_Testes/
│   ├── HTML/                   # Frontend pages
│   │   ├── home_page.html
│   │   ├── all_collections.html
│   │   ├── specific_collection.html
│   │   ├── item_page.html
│   │   ├── event_page.html
│   │   ├── team_page.html
│   │   └── user_page.html
│   ├── CSS/                    # Stylesheets
│   │   ├── general.css
│   │   ├── home_page.css
│   │   ├── specific_collection.css
│   │   ├── item_page.css
│   │   ├── events.css
│   │   ├── likes.css
│   │   └── ...
│   ├── JS/                     # Client-side logic
│   │   ├── app-data.js         # Data API wrapper
│   │   ├── app-events.js
│   │   ├── app-collections.js
│   │   ├── app-items.js
│   │   ├── app-users.js
│   │   ├── app-userpage.js
│   │   └── ...
│   ├── PHP/                    # Backend API
│   │   ├── auth.php            # Authentication
│   │   ├── get_all.php         # Data fetch endpoint
│   │   ├── config/
│   │   │   └── db.php          # Database connection (DAL)
│   │   └── crud/
│   │       ├── collections.php
│   │       ├── events.php
│   │       ├── items.php
│   │       ├── users.php
│   │       ├── ratings.php
│   │       └── relations.php
│   └── images/                 # Image assets
├── README.md
├── .git/                       # Git repository
└── SPRINT_2_REPORT_TEMPLATE.md # This file
```

---

## 10. Specifications & Considerations

### 10.1 Design Decisions

1. **VARCHAR for IDs:** Chose VARCHAR(100) for primary keys to support semantic identifiers (e.g., "collector-main", "pokemon-cards") alongside numeric IDs for flexibility

2. **Centralized Data API:** `get_all.php` serves as single read endpoint aggregating all data, reducing client-side API calls

3. **Session-Based Auth:** Preferred over token-based (JWT) for simplicity in a web application context

4. **Prepared Statements:** All database queries use parameterized statements for security and consistency

5. **Many-to-Many via Junction Tables:** Items can belong to multiple collections without data duplication; managed via `collection_items` table

6. **Timestamp Tracking:** All entities include `created_at` and `updated_at` for audit trail and sorting

### 10.2 Assumptions & Clarifications

- Users must be authenticated to create/modify collections and events
- Non-authenticated users can view all collections and items (read-only access)
- Each item's primary collection is referenced in `collection_id` field; additional collections via `collection_items` table
- Events are optional related entities; collections can exist without events
- Events can have ratings only after the event date has passed
- Password reset functionality not implemented (out of scope for Sprint 2)

### 10.3 Future Enhancements

1. **CSV Import/Export** — Load bulk data and export collections/items
2. **Advanced Search** — Faceted search, full-text search capability
3. **File Upload** — Direct image upload instead of URLs only
4. **Real-time Features** — WebSocket-based notifications for event updates
5. **Analytics** — Dashboard showing collection statistics, trending items
6. **Mobile App** — Native mobile application
7. **Payment Integration** — If system evolves to e-commerce
8. **Social Features** — Follow collectors, share collections, comments

---

## 11. Testing Procedures for Evaluators

### 11.1 How to Test the System

**Setup:**
1. Import `Database/sie_db.sql` into MySQL
2. Place project files in `htdocs/` (XAMPP)
3. Start Apache and MySQL
4. Navigate to `http://localhost/EIS_2526-G03_MEGI/Matheus_Testes/HTML/home_page.html`

**Test Scenarios:**

**Scenario 1: User Registration & Login**
1. Click "Log In" button on home page
2. Click "Sign Up" link
3. Fill in registration form (username, email, password, DOB, photo URL)
4. Click "Create Account"
5. Should redirect to login; log in with new credentials
6. Should see personalized profile page

**Scenario 2: Create & Manage Collection**
1. Log in with test account
2. Click "New Collection" button
3. Fill in collection details (name, type, summary, description, image URL)
4. Click "Save"
5. Should appear in collections list and profile
6. Try to edit/delete collection (should succeed as owner)
7. Try to delete as different user (should fail with 403 Forbidden)

**Scenario 3: Multi-Collection Item**
1. Create or select two collections
2. Add item to first collection
3. Edit item and assign to second collection
4. Verify item appears in both collections' item lists
5. Verify item details link to correct collection

**Scenario 4: Events**
1. Create event linked to collection
2. Add event date and location
3. RSVP to event (if authenticated user)
4. After event date, rate event 1-5 stars
5. Verify rating persists

**Scenario 5: Permission Validation**
1. Log in as User A
2. Create collection and item
3. Log in as User B (or view as anonymous)
4. Verify User B can see User A's collection (read-only)
5. Verify User B cannot edit/delete User A's collection
6. Try direct API call to delete User A's collection as User B (should return 403 Forbidden)

---

## 12. Self-Evaluation & Hetero-Evaluation

### 12.1 Self-Evaluation

#### Afonso Dias Fachada Ramos
- **Strengths:** [Self-assessment]
- **Weaknesses:** [Self-assessment]
- **Contribution Level:** [Rate 1-5]
- **Areas for Improvement:** [Self-assessment]

#### Ana Isabel Dias Cunha Amorim
- **Strengths:** [Self-assessment]
- **Weaknesses:** [Self-assessment]
- **Contribution Level:** [Rate 1-5]
- **Areas for Improvement:** [Self-assessment]

#### Filipa Marisa Duarte Mota
- **Strengths:** [Self-assessment]
- **Weaknesses:** [Self-assessment]
- **Contribution Level:** [Rate 1-5]
- **Areas for Improvement:** [Self-assessment]

#### Matheus Fernandes Vilhena Campinho
- **Strengths:** Database design, backend architecture, security implementation, consistent code quality
- **Weaknesses:** Limited frontend UI/UX work; could have implemented CSV import/export feature
- **Contribution Level:** 4.5/5
- **Areas for Improvement:** Add more comprehensive error logging and API documentation

### 12.2 Hetero-Evaluation

#### Evaluation of Afonso by Team
- [Comments from team members]

#### Evaluation of Ana by Team
- [Comments from team members]

#### Evaluation of Filipa by Team
- [Comments from team members]

#### Evaluation of Matheus by Team
- [Comments from team members]

---

## 13. Conclusion

This Sprint 2 project successfully implements a fully functional web-based information system for managing collections, items, events, and users with persistent data storage in a MySQL database. The system demonstrates:

✅ **Proper separation of concerns** with a three-tier architecture (Presentation, Business Logic, DAL)  
✅ **Complete CRUD operations** for all entities with REST-compliant endpoints  
✅ **Robust authentication and authorization** with session management and ownership verification  
✅ **Database integrity** through prepared statements and referential constraints  
✅ **User-friendly interface** with real-time updates and responsive design  

The team effectively utilized Git version control, followed an iterative development process, and maintained code quality throughout the project. While some advanced features (CSV import/export, advanced search) were not implemented due to time constraints, the core functionality meets all assignment requirements and provides a solid foundation for future enhancements.

---

## 14. Appendices

### Appendix A: Database Schema SQL
See `Database/sie_db.sql` for complete database implementation

### Appendix B: API Endpoint Documentation
See individual PHP files in `PHP/crud/` for endpoint specifications

### Appendix C: Git Commit History
View repository at: `https://github.com/mfvc-campinho/EIS_2526-G03_MEGI`

### Appendix D: File Directory Structure
Complete project structure as listed in Section 9.2

---

**Report Prepared By:** [Team Lead Name]  
**Date:** December 2025  
**Status:** Final Submission
