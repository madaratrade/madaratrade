Realtime Chat System (PHP + FastAPI + MongoDB)
A full‑stack realtime chat application built with PHP (frontend/server rendering), FastAPI (Python API services), MongoDB (message storage), and Dockerized infrastructure with Nginx.

The project combines a traditional PHP web interface with a modern Python microservice for chat operations, enabling scalable message handling and clean separation of responsibilities.

Architecture Overview
The system is composed of four main layers:

1. PHP Application

Handles authentication, sessions, and UI rendering
Displays chat list and chat area
Communicates with the FastAPI service using HTTP requests
Manages profile settings and file uploads
2. FastAPI Service

Handles chat-related APIs
Stores and retrieves messages from MongoDB
Calculates unseen messages per chat
Marks messages as seen
Provides data endpoints for the PHP frontend
3. MongoDB

Stores chat messages
Optimized for message history and aggregation
Used for unseen message counts and timestamps
4. Docker + Nginx

Containerized environment
Nginx acts as a reverse proxy
Routes requests between PHP and FastAPI services
Tech Stack
Frontend / Server Rendering

PHP
HTML / CSS / JavaScript
Backend API

Python
FastAPI
Database

MongoDB
Infrastructure

Docker
Docker Compose
Nginx
Main Features
User Profiles
Profile picture upload
Avatar preview
Profile editing
Chat System
One‑to‑one conversations
Message history stored in MongoDB
Last message preview in chat list
Timestamp formatting
Unseen Message Indicator
Blue notification dot for unread messages
Aggregated unseen counts per chat
API endpoint calculates unseen messages dynamically
Seen Status
Messages marked as seen when opened in chat area
Updates stored in MongoDB
UI reflects read status
Chat List UI
Displays:

User avatar
Display name
Last message
Timestamp
Unread message indicator

Project Structure (Simplified)
project-root
│
├── php-app/
│   ├── chat.php
│   ├── update_profile.php
│   ├── upload_profile_picture.php
│   └── db_connection.php
│
├─ fastapi/
│   ├── main.py
│   └── requirements.txt
│
├── nginx/
│   ── nginx.conf
│
├── docker-compose.yml
│
└── uploads/
    └── profile_images


API Endpoints (FastAPI)
Get Unseen Messages Per Chat
GET /unseen/{user_id}

Returns unseen message counts grouped by chat_id.

Example response:
{
  "chat_123": 2,
  "chat_456": 5
}

Send Message
POST /send

Stores a new message in MongoDB.

---

Mark Messages as Seen
POST /mark_seen/{chat_id}/{user_id}
Updates all unseen messages in the chat and marks them as read.
Updates all unseen messages in the chat and marks them as read.

---

Running the Project
1. Clone the repository
git clone https://github.com/yourusername/chat-system.git
cd chat-system

2. Start containers
docker compose up -d

3. Access the application
http://localhost

---

Key Implementation Details
Docker Networking
All services run inside a shared Docker network allowing:

Nginx → PHP
Nginx → FastAPI
FastAPI → MongoDB
Nginx Reverse Proxy
Routes requests:

/api/*  → FastAPI
/       → PHP

Future Improvements
Possible next steps:

WebSocket based realtime messaging
Typing indicators
Online/offline presence
Push notifications
Group chats
Message reactions
File and image sharing
Pagination for message history
License
MIT License
