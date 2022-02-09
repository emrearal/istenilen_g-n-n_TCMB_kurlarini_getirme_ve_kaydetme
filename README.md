# istenilen_gunun_TCMB_kurlarini_getirme_ve_kaydetme
<h3>İstenilen günün ( geçmiş veya günlük) TCMB( merkez bankası gösterge ) kurlarının getirilmesi ve saklanması.</h3>
PHP de yazılmıştır. Web sitenize entegre edip fatura veya muhasebe işlemleri için kur temin edebilirsiniz.<br>
Kendi gereksinimleriniz göre gerekli değişiklikleri yapabilirsiniz.<br><br>

<h4>Tcmb  döviz kuru arama ve kaydetme fonksiyonu/metodu:</h4> <br>
<ul>
<li>Her zaman bir gün öncesinin kuru aranır. (Çünkü bugün fatura kesecekseniz dünkü TCMB kurunu kullanırsınız.</li>
Aynı şekilde size gelmiş fatura 15 Ocak tarihli ise üzerindeki kur 14 Ocak kurudur.
Eğer sizin kur arama sebebiniz fatura veya muhasebe işlemleri değilse ilgili kodları -satır27- değiştiriniz.)</li>  
<li>Önce mysql'den aranır.</li>
<li>Yok ise tcmb'ye bağlanılıp kur aranır ve bulunursa hem cevap gönderilir hem de sql'e kaydedilir ki
sonraki aramalarda tekrar bağlanmaya gerek kalmasın.</li>
<li>Kur bulunamazsa bir gün öncesine bakılır.</li>
<li>12 gün geriye gidildiği halde kur bulunamazsa veya hata varsa veya tarih 2005 öncesi ise kur kullanıcıya "1" olarak iletilir
ve elle değiştirmesi beklenir. Bu "1" değeri veri tabanına kaydedilmez.</li>
<li>Bugünden daha ileri tarihler sorgulanırsa  bugünün tarihi sorgulanır.</li>
<li>İleride başka kurlara bakmak gerekirse diye tcmb'nin xml dosyasının tamamı mysql'e dizin olarak kaydedilir.</li>
<li>İlgili mysql tablosu (vt) şu şekilde oluşturulur:<br>
create table tcmbxml (tarih date unique, xmlverisi varchar(15000),primary key(tarih)) ;</li>
<li>ÖNEMLİ: Fonksiyonu çağırırken başına @ koyun . Böylece olası XML bağlantı hatası programı durdurmaz.</li>
</ul><br>
<u>emre@aral.web.tr</u>
     
     KULLANIMI: 
     $tarih='2022-01-15'  // 15 ocak 2022 döviz kurlarını çekiyoruz
     $dovizler=array();
     $dovizler=@dovizkuruver($tarih); 
     echo ($dovizler['eur']['alis']);  // 15 ocak tarihli TCMB euro alış kurunu yazdır. 
  
