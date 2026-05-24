<?php
session_start();

$user_id = $_SESSION['user_id'] ?? null;
$bio = $_POST['bio'] ?? "";

include "db.php";
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

if(!isset($_SESSION['user_id'])) {
	    header("Location: login.php");
	        exit;
}

$sender_id = $_SESSION['user_id'];
$bio     = $_POST['bio'] ?? "";
$username  = $_SESSION['username'];
$uuid      = $_SESSION['uuid'];
$receiver_id = $_GET['user_id'] ?? null;


$chat_id = $sender_id < $receiver_id
	    ? "{$sender_id}_{$receiver_id}"
	    : "{$receiver_id}_{$sender_id}";

$chat_id_org = $sender_id < $receiver_id
	    ? "{$sender_id}_{$receiver_id}"
	    : "{$receiver_id}_{$sender_id}";
?>

<?php

$current_user_id = $_SESSION['user_id'];

$api_url = "http://fastapi:8000/unseen/" . $current_user_id;

$response = file_get_contents($api_url);

$unseen_data = json_decode($response, true);

if (!$unseen_data) {
    $unseen_data = [];
}


?>

<?php

$api_url = "http://fastapi:8000/chats/" . $sender_id;

$response = file_get_contents($api_url);

$chats = json_decode($response, true);

?>

<?php

$chat_user_ids = array_column($chats, 'user_id');

$user_data_map = [];
if (!empty($chat_user_ids)) {
    $ids_string = implode(',', array_map('intval', $chat_user_ids));
        
    $query = "SELECT ua.id, ua.first_name, ua.last_name, ui.profile_picture 
              FROM users_account ua 
              LEFT JOIN users_info ui ON ua.id = ui.user_id 
              WHERE ua.id IN ($ids_string)";
                  
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $user_data_map[$row['id']] = [
            'name' => trim(($row['first_name'] ?? '') . " " . ($row['last_name'] ?? '')),
            'avatar' => !empty($row['profile_picture']) ? "uploads/" . $row['profile_picture'] : "uploads/default-avatar.png"
        ];
    }
}

?>

<?php
// تابع کمکی برای پیدا کردن مسیر عکس
function getAvatar($avatarFromDb) {
    $default = "uploads/default-avatar.png"; // مسیر عکس پیش‌فرض تو
        
            // اگر در دیتابیس خالی بود یا فایل وجود نداشت
    if (empty($avatarFromDb)) {
        return $default;
    }
                                
                                    // اگر آدرس کامل نبود، مسیر آپلود را به آن اضافه کن
    return "/uploads/" . $avatarFromDb;
}
?>

<?php

# Extract data from users_account table
$sql = "SELECT first_name, last_name, username, phone_number 
	        FROM users_account 
		        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();

# Etract photo and bio from users_info table (It could be empty)
$sql2 = "SELECT profile_picture, bio 
	         FROM users_info 
		          WHERE user_id = ?";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$info = $stmt2->get_result()->fetch_assoc();

# Preparing data to use
$my_name     = ($account['first_name'] ?? '') . " " . ($account['last_name'] ?? '');
$my_username = $account['username'] ?? "unknown";
$my_phone    = $account['phone_number'] ?? "N/A";
$my_bio      = $info['bio'] ?? "No bio yet...";

# Photo
$avatar_raw  = $info['profile_picture'] ?? "";
$my_avatar   = !empty($avatar_raw) ? "uploads/" . $avatar_raw : "uploads/default-avatar.png";

?>

<?php

$user_data_map = [];

$sql = "
SELECT ua.id, ua.first_name, ua.last_name, ui.profile_picture
FROM users_account ua
LEFT JOIN users_info ui ON ua.id = ui.user_id
";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {

	    $name = trim(($row['first_name'] ?? '') . " " . ($row['last_name'] ?? ''));

    $avatar = !empty($row['profile_picture'])
        ? "uploads/" . $row['profile_picture']
        : "uploads/default-avatar.png";

	        $user_data_map[$row['id']] = [
			        'name'   => $name,
				        'avatar' => $avatar
					    ];
}


?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Messenger</title>


<style>
#profileAvatar {
width: 120px;
height: 120px;
border-radius: 50%;
object-fit: cover;
}

</style>


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
padding-top:10px;
height:80px;
display:flex;
align-items:center;
padding:10px 20px;
background:#17212b;
border-bottom:1px solid #0e1621;
}

.chat-title{
color:white;
font-weight:600;
margin-left:10px;
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
        flex-direction: column;
        border-right: 1px solid #0e1621;
        color: var(--text-color);
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
	margin-top: 20px;
        border-radius: 50%;
        object-fit: cover;
    }


    .chat-avatar-area {
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

    .unseen-dot{
        width:8px;
        height:8px;
        background:#0084ff;
        border-radius:50%;
        display:inline-block;
        margin-left:6px;
    }

</style>


<style>

.main-wrapper{
    display:flex;
    height:100vh;
    width: 100%;
}

.sidebar{
    width:350px;
    min-width:350px;
}

.chat-area{
    flex:1;
    display:flex;
    flex-direction:column;
}

.messages{
    flex:1;
    overflow-y:auto;
}


</style>

<style>
.chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

#chat-header-normal {
display: flex;
align-items: center;
gap: 10px;
}

#chat-header-select {
width: 100%;
display: flex;
align-items: center;
justify-content: space-between;
}

#chat-header-select .select-left {
display: flex;
align-items: center;
gap: 8px;
}

#chat-header-select .select-right {
display: flex;
align-items: center;
gap: 10px;
}

.icon-btn {
background: transparent;
border: none;
color: #fff;
cursor: pointer;
font-size: 18px;
}

/* حالت انتخاب شده‌ی پیام (مثل تلگرام که یه های‌لایت می‌گیره) */
.message.selected {
background-color: rgba(255, 255, 255, 0.08);
}

/* منوی کنار هر پیام (برای لیک ساده، نه حالت select) */
.msg-actions {
position: absolute;
left:-120px;
top: -20px;
display: none;
flex-direction: column;
background: #222;
border-radius: 8px;
padding: 4px 6px;
z-index: 100;
box-shadow: 0 2px 10px rgba(0,0,0,0.5);
}

.msg-actions button {
background: transparent;
border: none;
color: #fff;
cursor: pointer;
font-size: 14px;
}

</style>

</head>

<body>

<div class="app">


<div class="main-wrapper">
     
<!-- SIDEBAR -->


<div class="sidebar">
    <div class="sidebar-header">
        <!-- آیکون پروفایل خودت کنار Chats -->
	<img src="<?php echo htmlspecialchars($my_avatar); ?>" class="my-profile-icon" onclick="openProfile()">
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
		$base_avatar = $user_data_map[$uid]['avatar'] ?? 'uploads/default-avatar.png';
		$avatar_url = $base_avatar . "?t=" . time();
		$is_active = (isset($_GET['user_id']) && $_GET['user_id'] == $uid) ? 'active' : '';
		$chat_id = $chat['chat_id'];
		$has_unseen = isset($unseen_data[$chat_id]) && $unseen_data[$chat_id] > 0;
            ?>
                <div class="chat-item <?= $is_active ?>" 
                     onclick="location.href='chat.php?user_id=<?= $uid ?>'">
                    
                    <div class="avatar-container">
                        <img src="<?= $avatar_url ?>" class="chat-avatar" onerror="this.src='uploads/default-avatar.png'">
                    </div>

                    <div class="chat-info">
                        <div class="chat-row">
                            <span class="user-name"><?= htmlspecialchars($display_name) ?></span>
                        </div>
                        <p class="last-message"><?= htmlspecialchars($chat['last_message']) ?></p>
		    </div>

                    <div class="chat-meta">
                        <span class="chat-time">
        		      <?php 
	         	      if (!empty($chat['timestamp'])) {
	                          echo date('H:i', strtotime($chat['timestamp']));
	                      }
		              ?>
		        </span>
		
		        <?php 
		        $chat_id = $chat['chat_id'];
		        if (isset($unseen_data[$chat_id]) && $unseen_data[$chat_id] > 0): 
		        ?>
		            <span class="unseen-dot"></span>
		        <?php endif; ?>
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

    <!-- CHAT HEADER -->
    <div class="chat-header">
        <!-- حالت عادی -->
        <div id="chat-header-normal">
            <img src="<?= $avatar_url ?>" class="chat-avatar-area"
                 onerror="this.src='uploads/default-avatar.png'">
            <div class="chat-title"><?= htmlspecialchars($display_name) ?></div>
        </div>

        <!-- حالت انتخاب (اول مخفی) -->
        <div id="chat-header-select" style="display: none;">
            <div class="select-left">
                <button class="icon-btn" onclick="exitSelectionMode()">
                    ✖
                </button>
                <span id="selected-count">0</span>
            </div>
            <div class="select-right">
                <!-- آیکون‌ها؛ متن‌شون مه نیست، بعداً با title می‌فهمی چیه -->
                <button class="icon-btn" id="btn-select-reply"    title="Reply">↩</button>
                <button class="icon-btn" id="btn-select-pin"      title="Pin">📌</button>
                <button class="icon-btn" id="btn-select-copy"     title="Copy">📋</button>
                <button class="icon-btn" id="btn-select-forward"  title="Forward">➡</button>
                <button class="icon-btn" id="btn-select-save"     title="Save">💾</button>
                <button class="icon-btn" id="btn-select-edit"     title="Edit">✏️</button>
                <button class="icon-btn" id="btn-select-delete"   title="Delete">🗑</button>
            </div>
        </div>
    </div>

    <div id="messages" class="messages"></div>

    <div class="input-area">
        <input id="msg" placeholder="Write a message">
        <button onclick="sendMessage()">Send</button>
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
	    <!-- <img id="user-avatar" src="<?= $my_avatar ?>" onerror="this.src='uploads/default-avatar.png'" alt="Profile"> -->
            <img 
                id="user-avatar"
                src="<?php echo htmlspecialchars($my_avatar); ?>" 
                alt="Profile"
                onclick="viewPhoto()"
                style="cursor:pointer;"
            >

                <div class="add-photo-icon">&#128247;</div>
            </div>
            
            <div class="user-main-info">
	        <h3 id="display-name"><?= htmlspecialchars($my_name) ?></h3>
		<p id="display-phone-user">@<?= htmlspecialchars($my_username) ?> | +<?= htmlspecialchars($my_phone) ?></p>
            </div>
        </div>

        <!-- Content List -->
        <div class="profile-list">
            <div class="list-item clickable" onclick="saveBio()">
                <div class="item-icon">ℹ️</div>
		<div class="item-text">
                    <small>About</small>
		    <span id="bio-text"><?= htmlspecialchars($my_bio) ?></span>
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

async function loadChats() {
    const res = await fetch(`/chats/${sender_id}`);
    const chats = await res.json();

    const list = document.querySelector(".chat-list");
    list.innerHTML = "";

    chats.forEach(c => {
        list.innerHTML += `
            <div class="chat-item" onclick="openChat('${c.chat_id}', ${c.user_id})">
                <div class="avatar">${c.user_id}</div>
                <div class="chat-info">
                    <div class="chat-name">User ${c.user_id}</div>
                    <div class="chat-last">${c.last_message}</div>
                </div>
            </div>
        `;
    });
}

</script>

<script>

const sender_id = <?= $sender_id ?>;
const receiver_id = <?= $receiver_id ?>;
const username = "<?= $username ?>";
const chat_id = "<?= $chat_id_org ?>";

const messages = document.getElementById("messages");

</script>

<script>

/* add message */

/* function addMessage(text,isSelf,time,isSeen){
/* 
/* 	/* For seen unseen tick  */
/* 	const tick = isSelf ? (isSeen ? "✓✓" : "✓") : "";
/* 
/* 	const div=document.createElement("div");
/* 
/* 	div.className="message "+(isSelf?"self":"other");
/* 
/* 	div.innerHTML = `
/* 		        <div>${text}</div>
/*         <div class="time">
/* 	    ${time}
/*             <span style="margin-left: 5px;">${tick}</span>
/*         </div>`;
/* 
/* 
/* 	messages.appendChild(div);
/* 
/* 	messages.scrollTop=messages.scrollHeight;
/* 
	/* } */

let selectionMode = false;
let selectedMessages = new Set();
let longPressTimer = null;

function addMessage(text, isSelf, time, isSeen, messageId) {
    const tick = isSelf ? (isSeen ? "✓✓" : "✓") : "";
    const div = document.createElement("div");
    div.className = "message " + (isSelf ? "self" : "other");
    div.id = "msg-" + messageId;

    div.style.position = "relative";
		
    div.innerHTML = `
	<div class="msg-content">${text}</div>
	<div class="time">${time} <span>${tick}</span></div>
	<div class="msg-actions">
	    <button onclick="msgAction(event, 'reply', ${messageId})">↩</button>
	    <button onclick="msgAction(event, 'pin', ${messageId})">📌</button>
	    <button onclick="msgAction(event, 'copy', ${messageId})">📋</button>
	    <button onclick="msgAction(event, 'forward', ${messageId})">➡</button>
	    <button onclick="msgAction(event, 'save', ${messageId})">💾</button>
	    <button onclick="msgAction(event, 'edit', ${messageId})">✏️</button>
	    <button onclick="msgAction(event, 'delete', ${messageId})">🗑</button>
	    <button class="btn-reaction" onclick="msgAction(event, 'reaction', ${messageId})">😊</button>
	</div>
    `;


    div.addEventListener("mousedown", (e) => {
        longPressTimer = setTimeout(() => {
	    enterSelectionMode();
	    toggleSelectMessage(div);
	}, 400); 
    });

    div.addEventListener("mouseup", () => {
        clearTimeout(longPressTimer);
    });

    div.addEventListener("click", (e) => {
        
        e.stopPropagation();
        if (selectionMode) {
            toggleSelectMessage(div);
	} else {
	    toggleMsgActions(div);
	}
    });

    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;

}



/* load history */

async function loadMessages() {
    const res = await fetch("http://" + window.location.host + ":8000/messages/" + chat_id);
    const data = await res.json();

    messages.innerHTML = "";

    data.forEach(m => {
        const time = new Date(m.timestamp).toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit"
        });

	addMessage(m.message, m.sender_id == sender_id, time, m.seen);
    });

    fetch(`http://` + window.location.host  + `:8000/seen/${chat_id}/${sender_id}`, {
        method: "POST"
    }).then(res => console.log("Messages marked as seen"))
}



/* websocket */

const ws=new WebSocket("ws://" + window.location.hostname + "/ws/" + sender_id);

ws.onmessage=e=>{

const data=JSON.parse(e.data);

if(data.chat_id===chat_id){

	const time=new Date().toLocaleTimeString([],{
	hour:"2-digit",
		minute:"2-digit"

});

/* addMessage(data.message,data.sender_id==sender_id,time); */
const isSelf = data.sender_id == sender_id;
addMessage(data.message, isSelf, time, isSelf ? false : null);

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


// Remove photo with confirmation
function deletePhoto() {
    if (confirm("Are you sure you want to delete your profile picture?")) {
        fetch('api/update_profile.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete_photo' })
        }).then(res => res.json()).then(data => {
            if(data.success) document.getElementById('user-avatar').src = 'uploads/default-avatar.png';
        });
    }
}

// Upload image

function uploadPhoto(input) {
    if (input.files && input.files[0]) {
	let formData = new FormData();
	formData.append('profile_picture', input.files[0]);
	formData.append('action', 'upload_photo');

        fetch('api/upload_profile_picture.php', {
            method: 'POST',
            body: formData
	})
	.then(res => res.json())
	.then(data => {
	    if (data.success) {
		document.getElementById('user-avatar').src = 'uploads/' + data.filename + '?t=' + Date.now();
		document.querySelector('.my-profile-icon').src = 'uploads/' + data.filename + '?t=' + Date.now();
	    } else {
		alert(data.message || "Uploaded");
            }
	})
	.catch(err => {
	    console.error(err);
	    alert("Upload error");
	});
    }
}

// Close profile tab
function closeProfile() {
    document.getElementById("profile-modal").style.display = "none";
}

// View photos
function viewPhoto() {
    const imgSrc = document.getElementById("user-avatar").src;

    const viewer = document.createElement("div");
    viewer.style.position = "fixed";
    viewer.style.top = "0";
    viewer.style.left = "0";
    viewer.style.width = "100%";
    viewer.style.height = "100%";
    viewer.style.background = "rgba(0,0,0,0.9)";
    viewer.style.display = "flex";
    viewer.style.alignItems = "center";
    viewer.style.justifyContent = "center";
    viewer.style.zIndex = "3000";
    viewer.style.cursor = "pointer";

    viewer.innerHTML = `
	<img src="${imgSrc}" style="max-width:90%; max-height:90%; border-radius:10px;">
    `;

    viewer.onclick = function () {
	viewer.remove();
    };

    document.body.appendChild(viewer);
}


function openProfile() {
    document.getElementById("profile-modal").style.display = "flex";
}

// function openChat(chat_id, user_id) {
//     window.location.href = "chat.php?chat_id=" + chat_id + "&user_id=" + user_id;
// }
//


// Edit bio directly
//function editBio() {
//    let currentBio = document.getElementById('bio-text').innerText;
//    let newBio = prompt("Write your bio:", currentBio);
//	        
//    if (newBio !== null) {
//	
//	// Send to server with fetch
//        fetch('update_bio.php', {
//            method: 'POST',
//            body: JSON.stringify({ action: 'update_bio', bio: newBio })
//        }).then(res => res.json()).then(data => {
//            if(data.success) document.getElementById('bio-text').innerText = newBio;
//    });
//  }
//}
//
//function saveBio() {
//    const newBio = document.getElementById("bio-input").value;
//
//    fetch("update_bio.php", {
//        method: "POST",
//        headers: { "Content-Type": "application/x-www-form-urlencoded" },
//        body: "bio=" + encodeURIComponent(newBio)
//    })
//    .then(res => res.text())
//    .then(msg => {
//        console.log(msg);
//        document.getElementById("bio-text").innerText = newBio;
//        alert("Bio saved!");
//    });
//}

function saveBio() {

    const bio = prompt("Enter your bio:");

    if (bio === null) return;

    console.log(bio);
    
    fetch("update_bio.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "bio=" + encodeURIComponent(bio)
    })
    .then(res => res.text())
    .then(data => console.log(data));

}



</script>

<input type="file" id="profilePicInput" accept="image/*" style="display:none;">


<script>
document.getElementById("profilePicInput").addEventListener("change", async function () {
    const file = this.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("user-avatar", file);

    try {
        const res = await fetch("api/upload_profile_picture.php", {
            method: "POST",
            body: formData
        });

        const text = await res.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            alert("Server error:\n" + text);
            return;
        }

        if (data.status === "success") {
            const newAvatar = data.file + "?t=" + new Date().getTime();

            const profileAvatar = document.getElementById("profileAvatar");
            const sidebarAvatar = document.getElementById("sidebarAvatar");

            if (profileAvatar) profileAvatar.src = newAvatar;
            if (sidebarAvatar) sidebarAvatar.src = newAvatar;

            alert("Profile picture updated successfully.");
        } else {
            alert(data.message || "Upload failed.");
        }
    } catch (err) {
        alert("Request failed.");
        console.error(err);
    }
});
</script>

</script>

<script>
function getTickIcon(isSelf, isSeen) {
    if (!isSelf) return ""; // تیک فقط برای پیام‌های ارسالی خودمان
    return isSeen ? "✓✓" : "✓";
}
					    
</script>

<script>
function toggleMsgActions(msgDiv) {

    // document.querySelectorAll('.msg-actions').forEach(m => m.style.display = 'none');
    // const actions = msgDiv.querySelector('.msg-actions');
    // if (!actions) return;
    // actions.style.display = 'flex';
	
    const actions = msgDiv.querySelector('.msg-actions');
    if (!actions) return;
    
    const isOpen = actions.style.display === 'flex';
    
    closeAllMsgActions();
    
    if (!isOpen) {
        actions.style.display = 'flex';
    }
}

function msgAction(event, action, messageId) {
    event.stopPropagation(); 
    console.log("Action:", action, "on", messageId);

    closeAllMsgActions();
    if (selectionMode && action === 'reaction') {

        return;
    }


}

function closeAllMsgActions() {
    document.querySelectorAll('.msg-actions').forEach(m => m.style.display = 'none');
}


/* --- Selection Mode --- */

function enterSelectionMode() {
    if (selectionMode) return;
    selectionMode = true;
    document.getElementById('chat-header-normal').style.display = 'none';
    document.getElementById('chat-header-select').style.display = 'flex';

    document.querySelectorAll('.btn-reaction').forEach(b => b.style.display = 'none');

    updateSelectionHeader();
}

function exitSelectionMode() {
    selectionMode = false;
    selectedMessages.clear();
    document.getElementById('chat-header-normal').style.display = 'flex';
    document.getElementById('chat-header-select').style.display = 'none';
    document.querySelectorAll('.message.selected').forEach(el => el.classList.remove('selected'));

    document.querySelectorAll('.btn-reaction').forEach(b => b.style.display = 'inline-flex');
}

function toggleSelectMessage(msgDiv) {
    const id = msgDiv.id; 
    if (msgDiv.classList.contains('selected')) {
        msgDiv.classList.remove('selected');
        selectedMessages.delete(id);
    } else {
        msgDiv.classList.add('selected');
        selectedMessages.add(id);
    }

    if (selectedMessages.size === 0) {
        exitSelectionMode();
    } else {
        updateSelectionHeader();
    }
}

function updateSelectionHeader() {
    const count = selectedMessages.size;
    document.getElementById('selected-count').innerText = count;

        const btnEdit = document.getElementById('btn-select-edit');

    if (count === 1) {
        btnEdit.style.display = 'inline-flex';
    } else {
        btnEdit.style.display = 'none';
    }

}

</script>

<script>
document.addEventListener('click', function (e) {
if (e.target.closest('.msg-actions') || e.target.closest('.message')) {
    return;
}
closeAllMsgActions();
});
					
</script>

</body>
</html>

