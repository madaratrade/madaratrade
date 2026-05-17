<?php
session_start();

$sender_id = $_SESSION['user_id'];
$username  = $_SESSION['username'];
$uuid      = $_SESSION['uuid'];
$receiver_id = $_GET['user'] ?? 2;

$chat_id = $sender_id < $receiver_id
	    ? "{$sender_id}_{$receiver_id}"
	        : "{$receiver_id}_{$sender_id}";
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

/* LEFT SIDEBAR */

.sidebar{
width:320px;
background:#17212b;
border-right:1px solid #0e1621;
display:flex;
flex-direction:column;
}

.sidebar-header{
padding:16px;
font-weight:bold;
color:white;
border-bottom:1px solid #0e1621;
}

.search{
padding:10px;
}

.search input{
width:100%;
padding:10px;
border-radius:20px;
border:none;
background:#0e1621;
color:white;
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

</style>
</head>

<body>

<div class="app">

<!-- SIDEBAR -->

<div class="sidebar">

<div class="sidebar-header">
Chats
</div>

<div class="search">
<input placeholder="Search">
</div>

<div class="chat-list">

<div class="chat-item">
<div class="avatar">A</div>
<div class="chat-info">
<div class="chat-name">ammighorabni</div>
<div class="chat-last">آخرین پیام...</div>
</div>
</div>

<div class="chat-item">
<div class="avatar">U</div>
<div class="chat-info">
<div class="chat-name">User 2</div>
<div class="chat-last">hello</div>
</div>
</div>

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



<script>
async function loadChats(){
	  const res = await fetch(`http://192.168.107.160:8000/chats/${sender_id}`);
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

	const res=await fetch("http://192.168.107.160:8000/messages/"+chat_id);

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

</script>

</body>
</html>

