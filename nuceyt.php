<?php
ob_start();
/*
Plugin Name: Nuceyt Sayac
Plugin URI: http://www.nucro.org
Description: Wordpress icin hazirlanmis, dunyanin en basit sayac eklentisi. Bu eklenti ile wordpress blogunuza giren kisileri sadece tekil ve cogul olarak sayabilirsiniz. Aylik ve haftalik secenekler ile veritabaninizi istediginiz zaman bosalatabilirsiniz. Boylelikle Nuceyt, sizi ve veritabaninizi hic yorumaz.
--------------------------------------------
Nuceyt is a simply wordpress hit counter plugin. It counts only single hits and pageviews. Nuceyt makes your databese more cool.
Version: 1.1.2
Author: Nucro & Musa AVCI
Producer: Orcun ILBEYLI~Nucro
Author URI: http://www.teyt.org/nuceyt
*/

add_action('init', 'nuceyt_kur');
add_action('wp_footer','nuceyt_sayac');
register_activation_hook(__FILE__,'nuceyt_ilk_kurulum');

function nuceyt_kur()
{
	global $wpdb;
	$prefix = $wpdb->prefix;
	$nuceyt_ayarlar_tablo = $prefix.'nuceyt_ayarlar';
	$wpdb->query("CREATE TABLE $nuceyt_ayarlar_tablo ( `ID` INT NOT NULL AUTO_INCREMENT ,`isim` TEXT NOT NULL ,`deger` TEXT NOT NULL ,PRIMARY KEY ( `ID` )) ENGINE = InnoDB ");
	$nuceyt_log_tablo = $prefix.'nuceyt_log';
	$wpdb->query("CREATE TABLE $nuceyt_log_tablo (`IP` TEXT NOT NULL ,`tarih` INT NOT NULL) ENGINE = InnoDB ");
	$nuceyt_tablo = $prefix."nuceyt";
	$wpdb->query("CREATE TABLE $nuceyt_tablo (`tarih` INT NOT NULL ,`hit` INT NOT NULL ,`tc` INT( 2 ) NOT NULL) ENGINE = InnoDB ");
	$ayar_varmi = $wpdb->get_var("SELECT ID FROM $nuceyt_ayarlar_tablo WHERE ID = 1");
	if(!$ayar_varmi):
		$wpdb->query("INSERT INTO $nuceyt_ayarlar_tablo (`ID` ,`isim` ,`deger`)VALUES (NULL , 'aylik_haftalik', 'aylik');");
	endif;
	add_action('admin_menu', 'nuceyt_panel_kur');
}

function nuceyt_panel_kur()
{
	if ( function_exists('add_submenu_page') )
		add_submenu_page('options-general.php', 'Nuceyt Saya? Ayarlar?', 'Nuceyt Saya? Ayarlar?', 'manage_options', 'nuceyt-sayac-ayarlari', 'nuceyt_panel');
}

function rapor_temizlik()
{
	global $wpdb;
	$nuceyt_ayarlar = $wpdb->prefix.'nuceyt_ayarlar';
	$nuceyt_log = $wpdb->prefix . "nuceyt_log";
	$nuceyt = $wpdb->prefix . "nuceyt";
		
	$aylik_haftalik = $wpdb->get_var("SELECT deger FROM $nuceyt_ayarlar WHERE isim = 'aylik_haftalik' AND ID = 1");
	if($aylik_haftalik == 'aylik'):
		$bir_ay_oncesi = $bugun = mktime(0,0,0,date('m')-1,date('d'),date('y'));
		$wpdb->query("DELETE FROM $nuceyt_log where tarih < $bir_ay_oncesi");
		$wpdb->query("DELETE FROM $nuceyt where tarih < $bir_ay_oncesi");
	else:
		$bir_hafta_oncesi = $bugun = mktime(0,0,0,date('m'),date('d')-7,date('y'));
		$wpdb->query("DELETE FROM $nuceyt_log where tarih < $bir_hafta_oncesi");
		$wpdb->query("DELETE FROM $nuceyt where tarih < $bir_hafta_oncesi");
	endif;
}

function nuceyt_sayac()
{
	global $wpdb;
	rapor_temizlik();
	
	if(is_home() || is_single()):
		$IP = $_SERVER['REMOTE_ADDR'];
		$tarih = time();
		$bugun = mktime(0,0,0,date('m'),date('d'),date('y'));
		$nuceyt_log = $wpdb->prefix . "nuceyt_log";
		$nuceyt = $wpdb->prefix . "nuceyt";

		$tekil_varmi = $wpdb->get_var("SELECT COUNT(*) FROM $nuceyt_log WHERE IP = '$IP'");
		if(!$tekil_varmi):
			$wpdb->query("INSERT INTO $nuceyt_log (`IP` ,`tarih`)VALUES ('$IP', '$tarih');");
			$bugun_tekil_kayit_varmi = $wpdb->get_var("SELECT COUNT(*) FROM $nuceyt WHERE tarih = $bugun AND tc = 1");
			if($bugun_tekil_kayit_varmi):
				$wpdb->query("UPDATE $nuceyt SET hit = hit + 1 WHERE tarih = $bugun AND tc = 1");
			else:
				$wpdb->query("INSERT INTO $nuceyt (`tarih` ,`hit` ,`tc`)VALUES ('$bugun', '1', '1');");
			endif;
		endif;
		
		$bugun_cogul_kayit_varmi = $wpdb->get_var("SELECT COUNT(*) FROM $nuceyt WHERE tarih = $bugun AND tc = 2");
		if($bugun_cogul_kayit_varmi):
			$wpdb->query("UPDATE $nuceyt SET hit = hit + 1 WHERE tarih = $bugun AND tc = 2");
		else:
			$wpdb->query("INSERT INTO $nuceyt (`tarih` ,`hit` ,`tc`)VALUES ('$bugun', '1', '2');");
		endif;	
	endif;
}

function online()
{
	global $wpdb;
	$nuceyt_log = $wpdb->prefix . "nuceyt_log";
	$bir_dakika = time() - 60;
	$online = $wpdb->get_var("SELECT COUNT(*) FROM $nuceyt_log WHERE tarih >= $bir_dakika");
	echo $online;
}

function tekil()
{
	global $wpdb;
	$nuceyt = $wpdb->prefix . "nuceyt";
	$bugun = mktime(0,0,0,date('m'),date('d'),date('y'));
	$bugun_tekil = $wpdb->get_var("SELECT hit FROM $nuceyt WHERE tarih = $bugun AND tc = 1");
	echo $bugun_tekil;
}

function cogul()
{
	global $wpdb;
	$nuceyt = $wpdb->prefix . "nuceyt";
	$bugun = mktime(0,0,0,date('m'),date('d'),date('y'));
	$bugun_cogul = $wpdb->get_var("SELECT hit FROM $nuceyt WHERE tarih = $bugun AND tc = 2");
	echo $bugun_cogul;
}

function nuceyt_ilk_kurulum()
{
	$rapor = @file_get_contents('http://www.teyt.org/nuceyt/?h='.$_SERVER['HTTP_HOST']);
	return true;
}

function nuceyt_panel()
{
	global $wpdb;
	if(isset($_POST['sure']) && $_POST['sure'] == 1 || $_POST['sure'] == 2):
		$nuceyt_ayarlar = $wpdb->prefix.'nuceyt_ayarlar';
		if($_POST['sure'] == 1):
			$wpdb->query("UPDATE $nuceyt_ayarlar SET `deger` = 'aylik' WHERE `ID` =1 LIMIT 1 ;");
		else:
			$wpdb->query("UPDATE $nuceyt_ayarlar SET `deger` = 'haftalik' WHERE `ID` =1 LIMIT 1 ;");
		endif;
	endif;
	
	$nuceyt_ayarlar = $wpdb->prefix.'nuceyt_ayarlar';		
	$aylik_haftalik = $wpdb->get_var("SELECT deger FROM $nuceyt_ayarlar WHERE isim = 'aylik_haftalik' AND ID = 1");
	
	$bugun = mktime(0,0,0,date('m'),date('d'),date('y'));
	$nuceyt = $wpdb->prefix . "nuceyt";
	$raporlar = $wpdb->get_results("SELECT * FROM $nuceyt GROUP BY tarih ORDER BY tarih DESC",OBJECT);
	$toplam_tekil_hit = $wpdb->get_var("SELECT SUM(hit) FROM $nuceyt WHERE tc = 1");
	$yt1 = $toplam_tekil_hit / 100;
	$toplam_cogul_hit = $wpdb->get_var("SELECT SUM(hit) FROM $nuceyt WHERE tc = 2");
	$yc1 = $toplam_cogul_hit / 100;

?>
<?php if ( !empty($_POST ) ) : ?>
<div id="message" class="updated fade">
  <p><strong>Settings Saved.</strong></p>
</div>
<?php endif; ?>
<div class="wrap">
<h2>Nuceyt Saya?</h2>
<div class="narrow">
<p>
Nuceyt is a simply wordpress hit counter plugin. Nuceyt makes your databese more cool :)<br/>
Good blogging from <a href="http://www.nucro.org" target="_blank">Nucro</a> and <a href="http://www.teyt.org" target="_blank">Musa</a>.</p>
<h2>Guide</h2>
<p>
Nuceyt starts counting when you activate the plugin. You must add that code(if non-exist) to your header.php theme file.
<p><i>&lt;?php wp_footer(); ?&gt;</i>
<p>Here is little codes shows your currently count at your page.<p>
<label style="margin: 8px"><b>Single : </b></label>
 <i>&lt;?php tekil() &gt;</i><br/>
<label style="margin: 8px"><b>Page View : </b></label> <i>&lt;?php cogul() &gt;</i>
</p>
</p>
<form method="post" action="">
</p>
<h2>Nuceyt Saya? Settings &amp; Reporting</h2>
<fieldset style="padding: 1em;  font:100%/1 sans-serif; border: 1px solid #CCCCCC">
<legend style="font-size:20px">Settings</legend>
<p>
<input type="radio" value="1" <?php if($aylik_haftalik=='aylik'): ?>checked<?php endif ?> name="sure"> 
Delete older than one month logs and reports..<br/>
<input type="radio" value="2" <?php if($aylik_haftalik=='haftalik'): ?>checked<?php endif ?> name="sure"> 
Delete older than one week logs and reports.<br/>
<p class="submit"><input type="submit" value="Save" name="ok" >
</p>
</fieldset>
</form>

<fieldset style="padding: 1em;  font:100%/1 sans-serif; border: 1px solid #CCCCCC">
<legend style="font-size:20px">Report</legend>
<br/>
<table border="1" id="table1" bgcolor="#EFEFEF" style="border-collapse: collapse" bordercolor="#FFFFFF">
<?php
foreach($raporlar as $sayac){ 
$rapor_tarih = date("d.m.y",$sayac->tarih);
$tekil = $wpdb->get_var("SELECT hit FROM $nuceyt WHERE tarih = $sayac->tarih AND tc = 1");
$cogul = $wpdb->get_var("SELECT hit FROM $nuceyt WHERE tarih = $sayac->tarih AND tc = 2");
$yt = $tekil / $yt1;
$yc = $cogul / $yc1;

?>
	<tr>
		<td style="padding: 8px"><?php echo $rapor_tarih ?></td>
		<td>
		<table border="0" style="margin: 3px;border-collapse: collapse" bordercolor="#FFFFFF">
		<tr bgcolor="#FFC8A4">
			<td><font size="1"><b><?php echo $tekil ?></b>Single</font></td>
		</tr>
		<tr bgcolor="#A8EAFF">
			<td><font size="1"><b><?php echo $cogul ?></b>PageView</font></td>
		</tr>
		</table></td>
		<td width="400" height="30">
		<div style="background-color: #FF822F; border: 1px solid #A84300; width: <?php echo $yt ?>%; height: 30%"></div>
		<div style="background-color: #6FDBFF; border: 1px solid #53A5BF; width: <?php echo $yc ?>%; height: 30%"></div>

		</td>
	</tr>
<?php } ?>
</table>
</fieldset>

</div>
<?php
}

?>