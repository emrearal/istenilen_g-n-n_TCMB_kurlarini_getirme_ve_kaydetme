<?php
/* 
İstenilen günün ( geçmiş veya günlük) TCMB( merkez bankası gösterge ) kurlarının getirilmesi ve saklanması.
PHP de yazılmıştır. Web sitenize entegre edip fatura veya muhasebe işlemleri için kur temin edebilirsiniz.
Kendi gereksinimleriniz göre gerekli değişiklikleri yapabilirsiniz.

*Tcmb  döviz kuru arama ve kaydetme fonksiyonu/metodu*
Her zaman bir gün öncesinin kuru aranır. (Çünkü bugün fatura kesecekseniz dünkü TCMB kurunu kullanırsınız.
Aynı şekilde size gelmiş fatura 15 Ocak tarihli ise üzerindeki kur 14 Ocak kurudur.
Eğer sizin kur arama sebebiniz fatura veya muhasebe işlemleri değilse ilgili kodları -satır27-değiştiriniz.)  
Önce mysql'den aranır.
Yok ise tcmb'ye bağlanılıp kur aranır ve bulunursa hem cevap gönderilir hem de sql'e kaydedilir ki
sonraki aramalarda tekrar bağlanmaya gerek kalmasın.
Kur bulunamazsa bir gün öncesine bakılır.
12 gün geriye gidildiği halde kur bulunamazsa veya hata varsa veya tarih 2005 öncesi ise kur kullanıcıya "1" olarak iletilir
ve elle değiştirmesi beklenir. Bu "1" değeri veri tabanına kaydedilmez.
Bugünden daha ileri tarihler sorgulanırsa  bugünün tarihi sorgulanır.
İleride başka kurlara bakmak gerekirse diye tcmb'nin xml dosyasının tamamı mysql'e dizin olarak kaydedilir.
İlgili mysql tablosu şu şekilde oluşturulur:
create table tcmbxml (tarih date unique, xmlverisi varchar(15000),primary key(tarih)) ;
ÖNEMLİ
fonksiyonu çağırırken başına @ koyun . Böylece olası XML bağlantı hatası programı durdurmaz.
emre@aral.web.tr 
*/
function dovizkuruver($tarih) {
    $tarih= (strtotime($tarih)>time()) ? (date('Y-m-d')) : $tarih; // sorgu ileri tarihli ise bakılacak tarih bugünün tarihidir.
    $kaydedilecektarih=$bakilantarih = date('Y-m-d', strtotime( $tarih . " -1 days")); // hep bir gün öncesine bakacağız
    // önce veritabanında var mı ona bakacağız
    $mysqli = new mysqli('mysqlurl','mysqlusername' ,'mysqlpassword' ,'mysqldatabase'); // bunları kendi veritabanına göre değiştir
    $mysqli->set_charset("utf8");
    if($mysqli->connect_error) { exit('Mysql Bağlantı hatası');}
    $doviz = array();
    $xmldizini= array();
    $sql = "SELECT xmlverisi from tcmbxml where tarih='$bakilantarih'" ;
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_result( $xmlverisi);
    $stmt->execute();
    $stmt->store_result();
    $sonucsayisi=$stmt->num_rows;
    
    if ($sonucsayisi!=0) {  // sql de varsa getir
        $stmt->fetch();
        $xmldizini=unserialize($xmlverisi);
        $stmt->close();
    } else { // sql'de yoksa tcmb ye bağlan ve getir
        $stmt->close();
        $b=0; // sayaç
        $kurubulamadim=true;
        do {  // kur yoksa tarih tatile denk gelmiştir.Bu durumda bulana kadar bir gün önceye bakacağız. do while döngüsü bunun için
            $yil=substr($bakilantarih, 0,4);
            $ay=substr($bakilantarih, 5,2);
            $gun=substr($bakilantarih, 8,2);
            $xmllinki="https://www.tcmb.gov.tr/kurlar/$yil$ay/$gun$ay$yil.xml";
            $xmlstring = simplexml_load_file($xmllinki,null,LIBXML_NOCDATA);
            $json = json_encode($xmlstring);
            $xmldizini = json_decode($json,TRUE); // xml stringini dizin'e bu şekilde çeviriyoruz.Önce json encode sonra json decode
            $kurvarmi=floatval($xmldizini['Currency']['0']['BanknoteBuying']);// dolar kuru sıfırsa kur bulunamamıştır
            if ($kurvarmi==0) { //  // kur yoksa bir gün öncesine bak
                $bakilantarih = date('Y-m-d', strtotime( $bakilantarih . " -1 days"));
            } else { // kur varsa döngüden çık
                $kurubulamadim=false;
            }
            $b++;
            if ($b==12) { $kurubulamadim=false;} // 12 gün geriye gidip bulamadıysan hata vardır , döngüden çık.
        } while ($kurubulamadim);  // kurubulamadım false olunca yani kur bulununca döngüden çık
        
        if ($kurvarmi!=0 && strtotime($tarih)>strtotime('2005-01-04')) { // eğer kur bulunduysa ve ytl sonrasıysa xml'i mysql'e kaydet
            $serilenmis=serialize($xmldizini);
            $komut="INSERT INTO tcmbxml (tarih,xmlverisi) VALUES ('$kaydedilecektarih','$serilenmis')";
            
            if ($mysqli->query($komut) === FALSE) {
                echo ("Veritabanı Kayıt Hatası:");
                echo $mysqli->error;
            }
        }
        $mysqli->close();
    } // sql'de yoksa else'i sonu
  // Kullanacağımız dövizler aşağıda. Dilersen yenilerini ekleyebilirsin. XML'den eklenecek dövizin currency sırasını bulman gerek
    $doviz['try']['alis'] = "1"; // TL nin tl kuru '1' dir.
    $doviz['try']['satis'] = "1";
    $doviz['try']['usdcapraz'] = $xmldizini['Currency']['0']['BanknoteBuying'];// tl nin dolar çapraz kuru zaten usd/tl kuruna eşittir.
    
    $doviz['usd']['alis'] = $xmldizini['Currency']['0']['BanknoteBuying'];
    $doviz['usd']['satis'] = $xmldizini['Currency']['0']['BanknoteSelling'];
    $doviz['usd']['usdcapraz'] ="1"; // doların dolar çapraz kuru 1'e eşittir. 
    
    $doviz['eur']['alis'] = $xmldizini['Currency']['3']['BanknoteBuying'];
    $doviz['eur']['satis'] =$xmldizini['Currency']['3']['BanknoteSelling'];
    $doviz['eur']['usdcapraz'] = $xmldizini['Currency']['3']['CrossRateOther'];// eur/usd 1'den büyük olduğundan CrossRateOther kullanıldı 
    
    $doviz['gbp']['alis'] = $xmldizini['Currency']['4']['BanknoteBuying'];
    $doviz['gbp']['satis'] = $xmldizini['Currency']['4']['BanknoteSelling'];
    $doviz['gbp']['usdcapraz'] =$xmldizini['Currency']['4']['CrossRateOther'];// gbp/usd 1'den büyük olduğundan CrossRateOther kullanıldı 
    
    $doviz['sek']['alis'] = $xmldizini['Currency']['6']['BanknoteBuying'];  // sek=İsveç Kronu
    $doviz['sek']['satis'] = $xmldizini['Currency']['6']['BanknoteSelling'];
    $doviz['sek']['usdcapraz'] =$xmldizini['Currency']['6']['CrossRateUSD'];// sek/usd 1'den küçük olduğundan CrossRateUSD kullanıldı 
    
    if (floatval($doviz['try']['usdcapraz'])==0 || strtotime($tarih)<strtotime('2005-01-04')) { // eğer kur bulunamadıysa veya ytl öncesi ise hepsi 1;
         foreach ($doviz as &$deger) {
            foreach ($deger as &$altdeger) {
                $altdeger="1";
            }
        }
        unset($altdeger); // never used demesin diye imha ettik;
    }
   return $doviz; // eğer tüm dövizleri göndermek istersen $doviz yerine $xmldizini'ni döndür
}// fonksiyon sonu

//ÖRNEK KULLANIM
$tarih='2022-01-15';  // 15 ocak 2022 döviz kurlarını çekiyoruz
$dovizler=array();
$dovizler=@dovizkuruver($tarih); 
echo ($dovizler['eur']['alis']);  // 15 ocak tarihli TCMB euro alış kurunu yazdır. 
?>
