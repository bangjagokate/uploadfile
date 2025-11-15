<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $fileUrls = [];

    // tangani banyak file
    foreach ($_FILES['file']['tmp_name'] as $i => $tmp) {
        if (!is_uploaded_file($tmp)) continue;

        $filePath = $tmp;
        $fileName = $_FILES['file']['name'][$i];

        $ch = curl_init();
        $post = [
            'reqtype' => 'fileupload',
            'fileToUpload' => new CURLFile($filePath, mime_content_type($filePath), $fileName)
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://catbox.moe/user/api.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (RetroUploader 1999)',
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || stripos($response, 'catbox.moe') === false) {
            $fileUrls[] = ['name' => $fileName, 'error' => $error ?: trim($response)];
        } else {
            $fileUrls[] = ['name' => $fileName, 'url' => trim($response)];
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['results' => $fileUrls]);
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Catbox Upload Station</title>
<style>
html,body{
  height:100%;
  margin:0;
  padding:0;
  font-family:Tahoma,Verdana,Arial,sans-serif;
  color:#000;
  background-color:#c0d9e8;
}
body::before{
  content:"";
  position:fixed;
  inset:0;
  background:url('https://files.catbox.moe/yvyj1x.jpg') center top/cover no-repeat;
  opacity:0.95;
  z-index:-1;
  filter:contrast(95%) saturate(95%);
}
.panel{
  width:520px;
  max-width:92%;
  margin:48px auto;
  background:#e9eef6;
  border:3px solid #1a4a6b;
  box-shadow:6px 6px 0 #7a7a7a;
  padding:16px;
  box-sizing:border-box;
}
.banner{
  background:linear-gradient(#1e5b83,#14435d);
  color:#fff;
  padding:8px 12px;
  font-weight:700;
  font-size:18px;
}
.row{margin-top:12px;}
label.filebox{
  display:block;
  background:#dbe8f4;
  border:2px solid #fff;
  box-shadow:inset 2px 2px 0 #ffffff,inset -2px -2px 0 #9aa9b8;
  padding:8px;
  cursor:pointer;
  font-weight:600;
  color:#08324a;
}
input[type=file]{display:none;}
.btn{
  display:inline-block;
  margin-top:10px;
  padding:8px 14px;
  background:#d4d0c8;
  border:2px solid;
  border-color:#fff #9a9a9a #9a9a9a #fff;
  cursor:pointer;
  font-weight:700;
}
.btn:active{border-color:#9a9a9a #fff #fff #9a9a9a;}
.progressWrap{
  margin-top:14px;
  border:1px solid #25485f;
  background:#ffffff;
  height:20px;
  width:100%;
  box-sizing:border-box;
}
.progressBar{
  height:100%;
  width:0%;
  background:repeating-linear-gradient(45deg,#a7dff6,#a7dff6 6px,#2b6fa1 6px,#2b6fa1 12px);
  transition:width .25s linear;
}
.status{margin-top:10px;font-size:13px;color:#102b3a;}
.terminal{
  margin-top:10px;
  background:#e6f4fb;
  border:1px solid #9bb7c9;
  padding:8px;
  font-family:"Courier New",monospace;
  font-size:12px;
  height:90px;
  overflow:auto;
  color:#073248;
}
.footer{
  margin-top:12px;
  font-size:12px;
  color:#0b3148;
  text-align:center;
  border-top:1px solid #c9dbe8;
  padding-top:8px;
}
#weatherBox{
  font-size:12px;
  color:#082b40;
  text-align:center;
  margin-top:6px;
}
</style>
</head>
<body>
<div class="panel">
  <div class="banner">Catbox Upload Station</div>

  <div class="row">
    <label class="filebox" for="fileInput">Pilih satu atau lebih file</label>
    <input type="file" id="fileInput" name="file[]" multiple>
  </div>

  <div class="row">
    <button class="btn" id="uploadBtn" type="button">Upload</button>
  </div>

  <div class="progressWrap"><div class="progressBar" id="progressBar"></div></div>

  <div class="status" id="statusText">Menunggu file</div>

  <div class="terminal" id="terminalLog">Sistem siap.</div>

  <div class="footer">
    File Upload by Bang Jago Kate | Telegram: 
    <a href="https://t.me/JagoKate" target="_blank">t.me/JagoKate</a>
    <div id="weatherBox">Mendapatkan informasi cuaca...</div>
  </div>
</div>

<script>
const fileInput=document.getElementById('fileInput');
const uploadBtn=document.getElementById('uploadBtn');
const progressBar=document.getElementById('progressBar');
const statusText=document.getElementById('statusText');
const terminalLog=document.getElementById('terminalLog');
const weatherBox=document.getElementById('weatherBox');

function logLine(text){
  const now=new Date();
  const time=now.getHours().toString().padStart(2,'0')+':'+now.getMinutes().toString().padStart(2,'0')+':'+now.getSeconds().toString().padStart(2,'0');
  terminalLog.innerHTML+='['+time+'] '+text+'<br>';
  terminalLog.scrollTop=terminalLog.scrollHeight;
}

uploadBtn.addEventListener('click',()=>{
  const files=fileInput.files;
  if(!files.length){alert('Pilih file terlebih dahulu');return;}
  progressBar.style.width='0%';
  statusText.textContent='Menyiapkan unggahan...';
  logLine('Menemukan '+files.length+' file.');

  const fd=new FormData();
  for(let i=0;i<files.length;i++){fd.append('file[]',files[i]);}

  const xhr=new XMLHttpRequest();
  xhr.open('POST','',true);
  xhr.upload.onprogress=(e)=>{
    if(e.lengthComputable){
      const percent=Math.floor((e.loaded/e.total)*100);
      progressBar.style.width=percent+'%';
      statusText.textContent='Mengunggah '+percent+'%';
    }
  };
  xhr.onloadstart=()=>{
    logLine('Mulai unggah...');
  };
  xhr.onload=()=>{
    if(xhr.status===200){
      try{
        const res=JSON.parse(xhr.responseText);
        if(res.results){
          progressBar.style.width='100%';
          statusText.textContent='Unggah selesai';
          logLine('Semua file selesai diunggah.');
          res.results.forEach(r=>{
            if(r.url)
              terminalLog.innerHTML+=`<b>${r.name}</b>: <a href="${r.url}" target="_blank">${r.url}</a><br>`;
            else
              terminalLog.innerHTML+=`<b>${r.name}</b>: Gagal (${r.error})<br>`;
          });
        }
      }catch(err){
        logLine('Kesalahan parsing respon: '+err.message);
      }
    }else{
      logLine('HTTP status: '+xhr.status);
      statusText.textContent='Gagal koneksi';
    }
  };
  xhr.onerror=()=>{statusText.textContent='Kesalahan jaringan';logLine('Kesalahan jaringan');};
  xhr.send(fd);
});

// === CUACA + JAM RETRO ===
function tampilJamCuaca(){
  const w=new Date();
  return w.getHours().toString().padStart(2,'0')+':'+w.getMinutes().toString().padStart(2,'0');
}
function dapatkanCuaca(lat,lon){
  fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true`)
  .then(r=>r.json()).then(d=>{
    if(d.current_weather){
      const suhu=Math.round(d.current_weather.temperature);
      weatherBox.textContent=`Suhu ${suhu}°C — ${tampilJamCuaca()}`;
    }else{weatherBox.textContent='Cuaca tidak tersedia';}
  }).catch(()=>weatherBox.textContent='Gagal memuat cuaca');
}
if(navigator.geolocation){
  navigator.geolocation.getCurrentPosition(p=>{
    dapatkanCuaca(p.coords.latitude,p.coords.longitude);
    setInterval(()=>dapatkanCuaca(p.coords.latitude,p.coords.longitude),300000);
  },()=>weatherBox.textContent='Tidak dapat mendeteksi lokasi');
}else weatherBox.textContent='Geolokasi tidak didukung';
setInterval(()=>{
  if(weatherBox.textContent.includes('—')){
    const bagian=weatherBox.textContent.split('—')[0];
    weatherBox.textContent=bagian+'— '+tampilJamCuaca();
  }
},1000);
</script>
</body>
</html>
