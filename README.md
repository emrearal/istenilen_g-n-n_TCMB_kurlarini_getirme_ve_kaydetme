# istenilen_gunun_TCMB_kurlarini_getirme_ve_kaydetme
İstenilen günün ( geçmiş veya günlük) TCMB( merkez bankası gösterge ) kurlarının getirilmesi ve saklanması.
PHP de yazılmıştır. Web sitenize entegre edip fatura veya muhasebe işlemleri için kur temin edebilirsiniz.
Kendi gereksinimleriniz göre gerekli değişiklikleri yapabilirsiniz.

Tcmb  döviz kuru arama ve kaydetme fonksiyonu/metodu:
     Her zaman bir gün öncesinin kuru aranır. Önce mysql'den aranır.
     Yok ise tcmb'ye bağlanılıp kur aranır ve bulunursa hem cevap gönderilir hem de sql'e kaydedilir ki
     sonraki aramalarda tekrar bağlanmaya gerek kalmasın.
     Kur bulunamazsa bir gün öncesine bakılır.
     12 gün geriye gidildiği halde kur bulunamazsa veya hata varsa veya tarih 2005 öncesi ise kur kullanıcıya "1" olarak iletilir
     ve elle değiştirmesi beklenir. Bu "1" değeri veri tabanına kaydedilmez.
     Bugünden daha ileri tarihler sorgulanırsa  bugünün tarihi sorgulanır.
     İleride başka kurlara bakmak gerekirse diye tcmb'nin xml dosyasının tamamı mysql'e dizin olarak kaydedilir.
     İlgili mysql tablosu (vt) şu şekilde oluşturulur:
     create table tcmbxml (tarih date unique, xmlverisi varchar(15000),primary key(tarih)) ;
     ÖNEMLİ
     Fonksiyonu çağırırken başına @ koyun . Böylece olası XML bağlantı hatası programı durdurmaz.
     emre@aral.web.tr
     
     KULLANIMI: 
     $tarih='2022-01-15'  // 15 ocak 2022 döviz kurlarını çekiyoruz
     $dovizler=array();
     $dovizler=@dovizkuruver($tarih); 
     echo ($dovizler['eur']['alis']);  // 15 ocak tarihli TCMB euro alış kurunu yazdır. 
  
