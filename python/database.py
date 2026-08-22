from pymongo import MongoClient

client = MongoClient("mongodb://mongodb:27017")

chat_db = client["chat_app"]
trade_db = client["madaratrade"]

<<<<<<< HEAD

messages = db["messages"]
posts = db["posts"]
=======
messages = chat_db["messages"]
posts = trade_db["posts"]
>>>>>>> d24f06f (Update / fix)
