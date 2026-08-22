from fastapi.middleware.cors import CORSMiddleware
from fastapi import FastAPI, WebSocket, HTTPException
<<<<<<< HEAD
=======
from pymongo import DESCENDING
>>>>>>> d24f06f (Update / fix)
from bson import ObjectId
from database import messages, posts
from datetime import datetime
from pydantic import BaseModel
from typing import Optional, List
import motor.motor_asyncio
import asyncio
import json

app = FastAPI()

# Cors configurations
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"]
)

origins = [
    "http://192.168.107.160",
    "http://192.168.107.160:80",
    "http://192.168.107.160:8080",
    "http://192.168.107.160:8000",
    "http://localhost",
    "http://localhost:80",
    "http://localhost:8080"
]

connections = []
online_users = set()


class EditMessageSchema(BaseModel):
    message_id: str
    new_content: str


class CreatePostSchema(BaseModel):
    user_id: int
    username: str
    account_no: Optional[str] = None
    character_no: Optional[str] = None
    garage_name: Optional[str] = None
    car_name: Optional[str] = None
    caption: Optional[str] = None
    tags: List[str] = []
    images: List[str] = []


class UpdatePostSchema(BaseModel):
    garage_name: Optional[str] = None
    car_name: Optional[str] = None
    caption: Optional[str] = None
    tags: Optional[List[str]] = None
    images: Optional[List[str]] = None


def serialize_post(doc):
    return {
        "id": str(doc["_id"]),
        "user_id": doc.get("user_id"),
        "username": doc.get("username"),
        "account_no": doc.get("account_no"),
        "character_no": doc.get("character_no"),
        "garage_name": doc.get("garage_name"),
        "car_name": doc.get("car_name"),
        "caption": doc.get("caption"),
        "tags": doc.get("tags", []),
        "images": doc.get("images", []),
        "likes_count": doc.get("likes_count", 0),
        "comments_count": doc.get("comments_count", 0),
        "created_at": doc["created_at"].isoformat() if doc.get("created_at") else None,
        "updated_at": doc["updated_at"].isoformat() if doc.get("updated_at") else None
    }


@app.get("/")
def home():
    return {"status": "chat server running"}

<<<<<<< HEAD

# Get posts by username for profile page
@app.get("/posts/by-username/{username}")
def get_posts_by_username(username: str):
=======
@app.get("/posts/by-username/{username}")
def get_posts_by_username(username: str):
    try:
        cursor = posts.find(
            {"username": username}
        ).sort("created_at", DESCENDING)

        result = []

        for post in cursor:
            post["_id"] = str(post["_id"])
            result.append(post)

        print(
            f"[GET POSTS] username={username!r}, "
            f"database={posts.database.name}, "
            f"collection={posts.name}, "
            f"count={len(result)}"
        )

        return result

    except Exception as error:
        print(f"[GET POSTS ERROR] {type(error).__name__}: {error}")

        raise HTTPException(
            status_code=500,
            detail="Failed to retrieve user posts"
        )

## Get posts by username for profile page
#@app.get("/posts/by-username/{username}")
#def get_posts_by_username(username: str):
#
#    docs = posts.find({"username": username}).sort("created_at", -1)
#
#    result = []
#    for d in docs:
#        result.append(serialize_post(d))
#
#    return result


# Get posts by user_id
@app.get("/posts/by-user/{user_id}")
def get_posts_by_user_id(user_id: int):

>>>>>>> d24f06f (Update / fix)
    docs = posts.find({"username": username}).sort("created_at", -1)

    result = []
    for d in docs:
        result.append(serialize_post(d))

    return result


<<<<<<< HEAD
# Get posts by user_id
@app.get("/posts/by-user/{user_id}")
def get_posts_by_user_id(user_id: int):
    docs = posts.find({"user_id": user_id}).sort("created_at", -1)

    result = []
    for d in docs:
        result.append(serialize_post(d))

    return result

=======
@app.get("/posts")
def list_posts(offset: int = 0, limit: int = 12, viewer_username: Optional[str] = None):
    try:
        safe_offset = max(offset, 0)
        safe_limit = max(1, min(limit, 50))

        cursor = posts.find({}).sort("created_at", DESCENDING).skip(safe_offset).limit(safe_limit)
        result = []

        for doc in cursor:
            result.append(serialize_post(doc))

        return {
            "status": "success",
            "posts": result,
            "count": len(result),
            "offset": safe_offset,
            "limit": safe_limit,
            "viewer_username": viewer_username
        }

    except Exception as error:
        print(f"[LIST POSTS ERROR] {type(error).__name__}: {error}")
        raise HTTPException(status_code=500, detail="Failed to retrieve posts")
>>>>>>> d24f06f (Update / fix)

# Get single post by MongoDB ObjectId
@app.get("/posts/{post_id}")
def get_post(post_id: str):
    if not ObjectId.is_valid(post_id):
        raise HTTPException(status_code=400, detail="Invalid post id")

    doc = posts.find_one({"_id": ObjectId(post_id)})

    if not doc:
        raise HTTPException(status_code=404, detail="Post not found")

    return serialize_post(doc)


# Create new post
@app.post("/posts")
def create_post(data: CreatePostSchema):
    doc = {
        "user_id": data.user_id,
        "username": data.username,
        "account_no": data.account_no,
        "character_no": data.character_no,
        "garage_name": data.garage_name,
        "car_name": data.car_name,
        "caption": data.caption,
        "tags": data.tags,
        "images": data.images,
        "likes_count": 0,
        "comments_count": 0,
        "created_at": datetime.utcnow(),
        "updated_at": None
    }

    result = posts.insert_one(doc)
    doc["_id"] = result.inserted_id

    return {
        "status": "success",
        "post": serialize_post(doc)
    }


# Update post
@app.put("/posts/{post_id}")
def update_post(post_id: str, data: UpdatePostSchema):
    if not ObjectId.is_valid(post_id):
        raise HTTPException(status_code=400, detail="Invalid post id")

    update_data = {}

    if data.garage_name is not None:
        update_data["garage_name"] = data.garage_name

    if data.car_name is not None:
        update_data["car_name"] = data.car_name

    if data.caption is not None:
        update_data["caption"] = data.caption

    if data.tags is not None:
        update_data["tags"] = data.tags

    if data.images is not None:
        update_data["images"] = data.images

    if not update_data:
        raise HTTPException(status_code=400, detail="No data to update")

    update_data["updated_at"] = datetime.utcnow()

    result = posts.update_one(
        {"_id": ObjectId(post_id)},
        {"$set": update_data}
    )

    if result.matched_count == 0:
        raise HTTPException(status_code=404, detail="Post not found")

    doc = posts.find_one({"_id": ObjectId(post_id)})

    return {
        "status": "success",
        "post": serialize_post(doc)
    }


# Delete post
@app.delete("/posts/{post_id}")
def delete_post(post_id: str):
    if not ObjectId.is_valid(post_id):
        raise HTTPException(status_code=400, detail="Invalid post id")

    result = posts.delete_one({"_id": ObjectId(post_id)})

    if result.deleted_count == 0:
        raise HTTPException(status_code=404, detail="Post not found")

    return {
        "status": "success",
        "deleted": True
    }


# Load messages in chat page
@app.get("/messages/{chat_id}")
def get_messages(chat_id: str):
    docs = messages.find({"chat_id": chat_id}).sort("timestamp", 1)

    result = []

    for d in docs:
        result.append({
            "id": str(d["_id"]),
            "uuid": d.get("uuid"),
            "chat_id": d["chat_id"],
            "sender_id": d["sender_id"],
            "receiver_id": d["receiver_id"],
            "message": d["message"],
            "user": d["user"],
            "timestamp": str(d["timestamp"]),
            "seen": d.get("seen", False)
        })

    return result


@app.get("/chats/{user_id}")
def get_chats(user_id: int):
    pipeline = [
        {
            "$match": {
                "$or": [
                    {"sender_id": user_id},
                    {"receiver_id": user_id}
                ]
            }
        },
        {"$sort": {"timestamp": -1}},
        {
            "$group": {
                "_id": "$chat_id",
                "last_message": {"$first": "$message"},
                "timestamp": {"$first": "$timestamp"},
                "sender_id": {"$first": "$sender_id"},
                "receiver_id": {"$first": "$receiver_id"}
            }
        }
    ]

    chats = list(messages.aggregate(pipeline))
    result = []

    for c in chats:
        other_user = c["receiver_id"] if c["sender_id"] == user_id else c["sender_id"]

        result.append({
            "chat_id": c["_id"],
            "user_id": other_user,
            "last_message": c["last_message"],
            "timestamp": str(c["timestamp"])
        })

    return result


# Online users
@app.get("/online")
def online():
    return list(online_users)


# Unseen api
@app.get("/unseen/{user_id}")
def get_unseen_counts(user_id: int):
    pipeline = [
        {"$match": {"receiver_id": user_id, "seen": False}},
        {"$group": {"_id": "$chat_id", "count": {"$sum": 1}}}
    ]

    results = list(messages.aggregate(pipeline))

    unseen_map = {}
    for item in results:
        unseen_map[item["_id"]] = item["count"]

    return unseen_map


# Change messages from unseen to seen API
@app.post("/seen/{chat_id}/{receiver_id}")
def mark_seen_api(chat_id: str, receiver_id: int):
    result = messages.update_many(
        {"chat_id": chat_id, "receiver_id": receiver_id, "seen": False},
        {"$set": {"seen": True}}
    )
    return {"status": "success", "updated": result.modified_count}


@app.post("/update_message")
def update_message(data: EditMessageSchema):
    try:
        if not ObjectId.is_valid(data.message_id):
            raise HTTPException(status_code=400, detail="Invalid message id")

        obj_id = ObjectId(data.message_id)

        result = messages.update_one(
            {"_id": obj_id},
            {"$set": {"message": data.new_content}}
        )

        if result.matched_count == 0:
            raise HTTPException(status_code=404, detail="Message not found")

        return {"status": "success"}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))


# Websocket endpoint
@app.websocket("/ws/{receiver_id}")
async def websocket_endpoint(websocket: WebSocket, receiver_id: str):
    await websocket.accept()
    connections.append(websocket)
    online_users.add(receiver_id)

    try:
        while True:
            data = await websocket.receive_text()

            # Parsing json
            payload = json.loads(data)

            sender_id = int(payload["sender_id"])
            receiver_id = int(payload["receiver_id"])
            chat_id = f"{min(sender_id, receiver_id)}_{max(sender_id, receiver_id)}"

            doc = {
                "chat_id": chat_id,
                "sender_id": sender_id,
                "receiver_id": receiver_id,
                "user": payload["user"],
                "uuid": payload["uuid"],
                "message": payload["message"],
                "timestamp": datetime.utcnow(),
                "seen": False
            }

            # Insert into database
            try:
                loop = asyncio.get_event_loop()
                insert_result = await loop.run_in_executor(None, lambda: messages.insert_one(doc))
                doc["_id"] = insert_result.inserted_id
            except Exception as e:
                print(f"MongoDB error: {e}")
                continue

            out = {
                "id": str(doc.get("_id")),
                "uuid": doc["uuid"],
                "chat_id": doc["chat_id"],
                "sender_id": doc["sender_id"],
                "receiver_id": doc["receiver_id"],
                "user": doc["user"],
                "message": doc["message"],
                "timestamp": doc["timestamp"].isoformat(),
                "seen": doc["seen"]
            }

            # Broadcast to users
            for conn in connections:
                await conn.send_text(json.dumps(out))

    except Exception:
        if websocket in connections:
            connections.remove(websocket)
        online_users.discard(receiver_id)
