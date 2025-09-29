# [M.EGI017] Teamwork 03 - Project Information System

The objective of this teamwork is to develop an information system for collectors. The development of this medium-sized web application has the goal to support hobbyists and collectors by providing an interactive, easy-to-use system for managing collections, their items, and related events.
All information about the collections are stored persistently in a relational database. 

The intended Information System (IS) is to manage collections (automotive miniatures, locomotive miniatures, stamps, coins, comic book classics, trading cards, among others) of collectible objects/parts/items, hereinafter simply called items. The information system to be developed will efficiently allow the CRUD (Create-Read-Update-Delete) of all information regarding the collections.

📌 **Main Features**

🗂️ **Collections & Items**
* Create, view, edit, and delete collections and items (CRUD).
* Items can belong to multiple collections without duplication (many-to-many relationship).
* Items include attributes such as importance (0–10), price, weight, acquisition date, and notes.
* Sorting and filtering by importance, monetary value, or weight.

📅 **Events**
* Events are linked to collections.
* System highlights upcoming events and raises alerts when dates are near.
* After an event, users can rate it (1–5) and leave feedback.

👤 **User Management**
* Basic user profile information.
* Each collection is associated with a user.

🧭 **Navigation & Interface**
* Interactive and user-friendly interface with contextual links between collections, items, and events.
* Home page shows the Top 5 collections and allows creation of new ones.

## Sprint 1 - Front-End (Client-Side)
Developed in HTML, CSS, and JavaScript (tested with mock data).

## Sprint 2 - Back-End (Server-Side)
Database + PHP
