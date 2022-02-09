<?php
// emre@aral.web.tr
function dovizkuruver($tarih) {
    $tarih= (strtotime($tarih)>time()) ? (date('Y-m-d')) : $tarih; // sorgu ileri tarihli ise bakılacak tarih bugünün tarihidir.
    $kaydedilecektarih=$bakilantarih = date('Y-m-d', strtotime( $tarih . " -1 days")); // hep bir gün öncesine bakacağız
    // önce veritabanında var mı ona bakacağız
    $mysqli = new mysqli('mysqlurl','mysqlusername' ,'mysqlpassword' ,'mysqldatabase'); // bunları kendi veritabanına göre değiştir
    $mysqli->set_charset("utf8");
    if($mysqli->connect_error) {
        exit('Bağlantı hatası');
    }
    $doviz = array();
    $xmldizini= array();
    $sql = "SELECT xmlverisi from tcmbxml where tarih='$bakilantarih'" ;
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_result( $xmlverisi);
    $stmt->execute();
    $stmt->store_result();
    $sonucsayisi=$stmt->num_rows;
    
    if ($sonucsayisi!=0) {  // vt de varsa getir
        $stmt->fetch();
        $xmldizini=unserialize($xmlverisi);
        $stmt->close();
    } else { // vt de yoksa tcmb ye bağlan ve getir
        $stmt->close();
        $b=0;
        $kurubulamadim=true;
        do {
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
        } while ($kurubulamadim);
        
        if ($kurvarmi!=0 && strtotime($tarih)>strtotime('2005-01-04')) { // eğer kur bulunduysa ve ytl sonrasıysa ve ileri tarih değilse xml'i vt'ye kaydet
            $serilenmis=serialize($xmldizini);
            $komut="INSERT INTO tcmbxml (tarih,xmlverisi) VALUES ('$kaydedilecektarih','$serilenmis')";
            
            if ($mysqli->query($komut) === FALSE) {
                echo ("Veritabanı Kayıt Hatası:");
                echo $mysqli->error;
            }
        }
        $mysqli->close();
    } // vt 'de yoksa else'i sonu
    
    $doviz['try']['alis'] = "1";
    $doviz['try']['satis'] = "1";
    $doviz['try']['usdcapraz'] = $xmldizini['Currency']['0']['BanknoteBuying'];
    
    //Döviz cinsi ekleyeceksen eskisini silme veya değiştirme .Sadece en alta yenisini ekle
    $doviz['usd']['alis'] = $xmldizini['Currency']['0']['BanknoteBuying'];
    $doviz['usd']['satis'] = $xmldizini['Currency']['0']['BanknoteSelling'];
    $doviz['usd']['usdcapraz'] ="1";
    
    $doviz['eur']['alis'] = $xmldizini['Currency']['3']['BanknoteBuying'];
    $doviz['eur']['satis'] =$xmldizini['Currency']['3']['BanknoteSelling'];
    $doviz['eur']['usdcapraz'] = $xmldizini['Currency']['3']['CrossRateOther'];
    
    $doviz['gbp']['alis'] = $xmldizini['Currency']['4']['BanknoteBuying'];
    $doviz['gbp']['satis'] = $xmldizini['Currency']['4']['BanknoteSelling'];
    $doviz['gbp']['usdcapraz'] =$xmldizini['Currency']['4']['CrossRateOther'];
    
    $doviz['sek']['alis'] = $xmldizini['Currency']['6']['BanknoteBuying'];
    $doviz['sek']['satis'] = $xmldizini['Currency']['6']['BanknoteSelling'];
    $doviz['sek']['usdcapraz'] =$xmldizini['Currency']['6']['CrossRateUSD'];
    
    if (floatval($doviz['try']['usdcapraz'])==0 || strtotime($tarih)<strtotime('2005-01-04')) { // eğer kur bulunamadıysa veya ytl öncesi ise hepsi 1;
         foreach ($doviz as &$deger) {
            foreach ($deger as &$altdeger) {
                $altdeger="1";
            }
        }
        unset($altdeger); // never used demesin diye imha ettik;
    }
    return $doviz; // eğer tüm dövizleri göndermek istersen $doviz yerine $xmldizini'ni döndür
}// fonk sonu
?>
