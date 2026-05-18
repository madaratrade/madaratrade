<?php
session_start();

#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

if(!isset($_SESSION['user_id'])) {
	    header("Location: login.php");
	        exit;
}

$sender_id = $_SESSION['user_id'];
$username  = $_SESSION['username'];
$uuid      = $_SESSION['uuid'];
$receiver_id = $_GET['user'] ?? 2;

$chat_id = $sender_id < $receiver_id
	    ? "{$sender_id}_{$receiver_id}"
	    : "{$receiver_id}_{$sender_id}";

?>

<?php

$api_url = "http://fastapi:8000/chats/" . $sender_id;

$response = file_get_contents($api_url);

$chats = json_decode($response, true);

?>

<?php
// تابع کمکی برای پیدا کردن مسیر عکس
function getAvatar($avatarFromDb) {
    $default = "/uploads/default-avatar.png"; // مسیر عکس پیش‌فرض تو
        
            // اگر در دیتابیس خالی بود یا فایل وجود نداشت
    if (empty($avatarFromDb)) {
        return $default;
    }
                                
                                    // اگر آدرس کامل نبود، مسیر آپلود را به آن اضافه کن
    return "/uploads/" . $avatarFromDb;
}
?>




<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Messenger</title>


<style>

*{
box-sizing:border-box;
}

body{
margin:0;
font-family:system-ui;
background:#0e1621;
height:100vh;
overflow:hidden;
}

/* MAIN LAYOUT */

.app{
display:flex;
height:100vh;
}


/* CHAT LIST */

.chat-list{
flex:1;
overflow-y:auto;
}

.chat-item{
display:flex;
padding:12px;
cursor:pointer;
border-bottom:1px solid #0e1621;
}

.chat-item:hover{
background:#202b36;
}

.avatar{
width:45px;
height:45px;
border-radius:50%;
background:#2f80ed;
display:flex;
align-items:center;
justify-content:center;
color:white;
font-weight:bold;
margin-right:10px;
}

.chat-info{
flex:1;
}

.chat-name{
color:white;
font-size:14px;
}

.chat-last{
font-size:12px;
color:#8a98a8;
}

/* RIGHT CHAT */

.chat-area{
flex:1;
display:flex;
flex-direction:column;
background:#0e1621;
}

/* CHAT HEADER */

.chat-header{
height:60px;
display:flex;
align-items:center;
padding:10px 20px;
background:#17212b;
border-bottom:1px solid #0e1621;
}

.chat-title{
color:white;
font-weight:600;
}

/* MESSAGES */

.messages{
flex:1;
overflow-y:auto;
padding:20px;
}

.message{
max-width:60%;
padding:10px 14px;
margin-bottom:10px;
border-radius:10px;
font-size:14px;
}

.self{
margin-left:auto;
background:#2f80ed;
color:white;
}

.other{
background:#202b36;
color:white;
}

.time{
font-size:11px;
opacity:.7;
margin-top:4px;
text-align:right;
}

/* INPUT */

.input-area{
padding:12px;
background:#17212b;
display:flex;
}

.input-area input{
flex:1;
border:none;
padding:12px;
border-radius:20px;
background:#0e1621;
color:white;
}

.input-area button{
margin-left:10px;
border:none;
background:#2f80ed;
color:white;
padding:10px 18px;
border-radius:20px;
cursor:pointer;
}

.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.7); display: flex; justify-content: center; align-items: center; z-index: 1000;
}
.profile-card {
    background: #1e2428; width: 400px; border-radius: 15px; overflow: hidden; color: white; font-family: sans-serif;
}
.profile-header {
    display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #2d353b;
}
.close-btn { background: none; border: none; color: #888; font-size: 24px; cursor: pointer; }
.menu-dots { background: none; border: none; color: #888; font-size: 20px; cursor: pointer; }

.profile-main { text-align: center; padding: 20px; }
.avatar-container { 
    position: relative; width: 100px; height: 100px; margin: 0 auto; cursor: pointer; 
}
#user-avatar { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.add-photo-icon {
    position: absolute; bottom: 0; left: 0; background: #3498db; border-radius: 50%; padding: 5px; font-size: 12px;
}

.profile-list { padding: 10px 0; }
.list-item { 
    display: flex; align-items: center; padding: 12px 20px; cursor: pointer; transition: 0.2s;
}
.list-item:hover { background: #2d353b; }
.item-icon { margin-right: 15px; font-size: 18px; color: #888; }
.item-text span { display: block; font-size: 15px; }
.item-text small { color: #888; font-size: 12px; }

/* Dropdown Menu */
.dropdown { position: relative; }
.dropdown-content {
    display: none; position: absolute; right: 0; background: #2d353b; min-width: 150px; border-radius: 8px; z-index: 1;
}
.dropdown-content a { color: white; padding: 10px; display: block; text-decoration: none; font-size: 14px; }
.dropdown-content a:hover { background: #3e4850; }

/* هدر سایدبار */
</style>

<style>
    :root {
        --sidebar-bg: #17212b;
        --sidebar-hover: #232e3c;
        --text-color: #ffffff;
        --text-secondary: #7f91a4;
        --accent-color: #2b5278;
    }

    .sidebar {
        width: 350px;
        height: 100vh;
        background: var(--sidebar-bg);
        display: flex;
        flex-direction: column;
        border-right: 1px solid #0e1621;
        color: var(--text-color);
        position: fixed;
        left: 0;
        top: 0;
    }

    /* هدر سایدبار */
    .sidebar-header {
        padding: 10px 15px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .my-profile-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
        border: 1px solid #2b5278;
    }

    .sidebar-header h2 {
        font-size: 1.2rem;
        flex: 1;
    }

    .header-actions i {
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 1.1rem;
    }

    /* لیست چت‌ها */
    .chat-list {
        flex: 1;
        overflow-y: auto;
    }

    .chat-item {
        display: flex;
        padding: 10px 15px;
        gap: 12px;
        cursor: pointer;
        transition: background 0.1s;
        align-items: center;
    }

    .chat-item:hover {
        background: var(--sidebar-hover);
    }

    .chat-item.active {
        background: var(--accent-color);
    }

    .avatar-container {
        position: relative;
    }

    .chat-avatar {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        object-fit: cover;
    }

    .chat-info {
        flex: 1;
        min-width: 0; /* برای کار کردن text-overflow */
    }

    .chat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }

    .user-name {
        font-weight: 600;
        font-size: 1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .chat-time {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    .last-message {
        font-size: 0.9rem;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin: 0;
    }
</style>

</head>

<body>

<div class="app">

<!-- SIDEBAR -->


<div class="sidebar">
    <div class="sidebar-header">
        <!-- آیکون پروفایل خودت کنار Chats -->
        <img src="/uploads/default-avatar.png" class="my-profile-icon" onclick="location.href='profile.php'">
        <h2>Chats</h2>
        <div class="header-actions">
            <i class="fas fa-search"></i>
        </div>
    </div>

    <div class="chat-list">
        <?php if (!empty($chats)): ?>
            <?php foreach ($chats as $chat): 
                $uid = $chat['user_id'];
                $display_name = $user_data_map[$uid]['name'] ?? "User " . $uid;
                $avatar_url = getAvatar($user_data_map[$uid]['avatar'] ?? '');
                $is_active = (isset($_GET['user_id']) && $_GET['user_id'] == $uid) ? 'active' : '';
            ?>
                <div class="chat-item <?= $is_active ?>" 
                     onclick="location.href='chat.php?chat_id=<?= $chat['chat_id'] ?>&user_id=<?= $uid ?>'">
                    
                    <div class="avatar-container">
                        <img src="<?= $avatar_url ?>" class="chat-avatar" onerror="this.src='/uploads/default-avatar.png'">
                    </div>

                    <div class="chat-info">
                        <div class="chat-row">
                            <span class="user-name"><?= htmlspecialchars($display_name) ?></span>
                            <span class="chat-time"><?= date("H:i", strtotime($chat['timestamp'])) ?></span>
                        </div>
                        <p class="last-message"><?= htmlspecialchars($chat['last_message']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; color: var(--text-secondary);">No chats found</div>
        <?php endif; ?>
    </div>
</div>

<!-- CHAT AREA -->

<div class="chat-area">

<div class="chat-header">
<div class="chat-title">Chat with User <?= $receiver_id ?></div>
</div>

<div id="messages" class="messages"></div>

<div class="input-area">
<input id="msg" placeholder="Write a message">
<button onclick="sendMessage()">Send</button>
</div>

</div>

</div>

<!-- Modal Background -->
<div id="profile-modal" class="modal-overlay" style="display:none;">
    <div class="profile-card">
        <!-- Header -->
        <div class="profile-header">
            <button onclick="closeProfile()" class="close-btn">&times;</button>
            <span class="header-title">User account</span>
            <div class="dropdown">
                <button class="menu-dots" onclick="toggleMenu()">&#8942;</button>
                <div id="menu-content" class="dropdown-content">
                    <a href="#" onclick="triggerUpload()">Add Photo</a>
                    <a href="logout.php" style="color: #ff5e5e;">Logout</a>
                </div>
            </div>
        </div>

        <!-- Profile Section -->
        <div class="profile-main">
            <div class="avatar-container" onclick="viewPhoto()">
                <img id="user-avatar" src="src/uploads/default-avatar.png" alt="Profile">
                <div class="add-photo-icon">&#128247;</div>
            </div>
            
            <div class="user-main-info">
                <h3 id="display-name">Name</h3>
                <p id="display-phone-user">@username | +phone</p>
            </div>
        </div>

        <!-- Content List -->
        <div class="profile-list">
            <div class="list-item clickable" onclick="editBio()">
                <div class="item-icon">ℹ️</div>
                <div class="item-text">
                    <span id="bio-text">No bio yet...</span>
                    <small>درباره من</small>
                </div>
            </div>

	                <div class="list-item clickable">
			                <div class="item-icon">🔖</div>
					                <div class="item-text">Save Message</div>
							            </div>

            <div class="list-item clickable">
                <div class="item-icon">⚙️</div>
                <div class="item-text">Settings</div>
            </div>

            <div class="list-item clickable">
                <div class="item-icon">👥</div>
                <div class="item-text">Create Group</div>
            </div>

            <div class="list-item clickable">
                <div class="item-icon">📢</div>
                <div class="item-text">Create Channel</div>
            </div>

            <div class="list-item clickable">
                <div class="item-icon">🎧</div>
                <div class="item-text">Support</div>
            </div>
        </div>
    </div>
    </div>

    <!-- Hidden File Input -->
<input type="file" id="photo-input" style="display:none" onchange="uploadPhoto(this)">


<script>
async function loadChats(){
	  const res = await fetch(`http://fastapi:8000/chats/${sender_id}`);
	  const chats = await res.json();
	  
	  const list = document.querySelector(".chat-list");
	  list.innerHTML = "";
	  
	  chats.forEach(c=>{
	    list.innerHTML += `
	      <div class="chat-item" onclick="openChat(${c.user_id})">
	        <div class="avatar">${c.user_id}</div>
	        <div class="chat-info">
	          <div class="chat-name">User ${c.user_id}</div>
	          <div class="chat-last">${c.last_message}</div>
	        </div>
	      </div>`;
	  });
}
	  
</script>

<script>

const sender_id = <?= $sender_id ?>;
const receiver_id = <?= $receiver_id ?>;
const username = "<?= $username ?>";
const chat_id = "<?= $chat_id ?>";

const messages = document.getElementById("messages");

/* add message */

function addMessage(text,isSelf,time){

	const div=document.createElement("div");

	div.className="message "+(isSelf?"self":"other");

	div.innerHTML=`
		<div>${text}</div>
		<div class="time">${time}</div>
`;

	messages.appendChild(div);

	messages.scrollTop=messages.scrollHeight;

}

/* load history */

async function loadMessages(){

	const res=await fetch("http://fastapi:8000/messages/"+chat_id);

	const data=await res.json();

	data.forEach(m=>{

	const time=new Date(m.timestamp).toLocaleTimeString([],{
	hour:"2-digit",
		minute:"2-digit"
	});

	addMessage(m.message,m.sender_id==sender_id,time);

	});

}

/* websocket */

const ws=new WebSocket("ws://192.168.107.160/ws/"+sender_id);

ws.onmessage=e=>{

const data=JSON.parse(e.data);

if(data.chat_id===chat_id){

	const time=new Date().toLocaleTimeString([],{
	hour:"2-digit",
		minute:"2-digit"
});

addMessage(data.message,data.sender_id==sender_id,time);

}

};

/* send */

function sendMessage(){

	const input=document.getElementById("msg");

	const text=input.value.trim();

	if(!text)return;

	ws.send(JSON.stringify({

	chat_id:chat_id,
		sender_id:sender_id,
		receiver_id:receiver_id,
		user:username,
		uuid:"<?= $uuid ?>",
		message:text
	}));

	input.value="";

}

loadMessages();

// Open three dot meno
function toggleMenu() {
    let menu = document.getElementById('menu-content');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

// Choose photo
function triggerUpload() {
    document.getElementById('photo-input').click();
}

// Edit bio directly
function editBio() {
    let currentBio = document.getElementById('bio-text').innerText;
    let newBio = prompt("Write your bio:", currentBio);
	        
    if (newBio !== null) {
	
	// Send to server with fetch
        fetch('api/update_profile.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'update_bio', bio: newBio })
        }).then(res => res.json()).then(data => {
            if(data.success) document.getElementById('bio-text').innerText = newBio;
    });
  }
}

// Remove photo with confirmation
function deletePhoto() {
    if (confirm("Are you sure you want to delete your profile picture?")) {
        fetch('api/update_profile.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete_photo' })
        }).then(res => res.json()).then(data => {
            if(data.success) document.getElementById('user-avatar').src = 'src/uploads/default-avatar.png';
        });
    }
}

// Upload image
function uploadPhoto(input) {
    if (input.files && input.files[0]) {
        let formData = new FormData();
	formData.append('photo', input.files[0]);
        formData.append('action', 'upload_photo');

        fetch('api/update_profile.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json()).then(data => {
            if(data.success) {
                document.getElementById('user-avatar').src = 'src/uploads/' + data.filename;
            }
        });
    }
}

// Close profile tab
function closeProfile() {
    document.getElementById("profile-modal").style.display = "none";
}

// View photos
function viewPhoto() {
    alert("Photo viewer بخش بعدی است. اگر خواستی برات کاملش می‌سازم.");
}

function openChat(chat_id, user_id) {
    window.location.href = "chat.php?chat_id=" + chat_id + "&user_id=" + user_id;
}


</script>

</body>
</html>

