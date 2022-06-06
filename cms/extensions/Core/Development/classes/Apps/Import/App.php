<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\Import;

class App extends \Frootbox\Admin\Persistence\AbstractApp {

    protected $onInsertDefault = [
        'menuId' => 'Frootbox\\Ext\\Core\\Development'
    ];


    /**
     * 
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function importAction (
        \DI\Container $container
    )
    {

        $source = file_get_contents($_FILES['file']['tmp_name']);

        $payload = json_decode($source, true);

        foreach ($payload['sections'] as $section) {

            $className = $section['adapter'];
            $adapter = new $className;

            $adapter->setItems($section['items']);

            $container->call([ $adapter, 'execute' ]);

            d($adapter);
        }
    }
    
    
    
    
    /**
     *
     */
    public function fppAction (
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    )
    {

        /**
         * Set date and source
         */
        $result = $articles->fetch([
            'calcFoundRows' => true,
            'where' => [ 'state' => 'ThumbnailDone' ],
            'limit' => 20
        ]);

        $result->getTotal();

        foreach ($result as $article) {

            $file = $article->getConfig('import.url');

            switch (true) {

                case preg_match('#_MT_#', $file):
                    $source = 'Meppener Tagespost';
                break;

                case preg_match('#_LT[_\-]#', $file):
                    $source = 'Lingener Tagespost';
                break;

                case preg_match('#_NOZ_#', $file):
                    $source = 'Neue Osnabrücker Presse';
                break;

                default:
                    d($article);
            }

            $article->addConfig([
                'source' => $source
            ]);

            $article->save();
        }

        d("Fertig!");


        /**
         * Create filled files
         */
        $result = $articles->fetch([
            'calcFoundRows' => true,
            'where' => [ 'state' => 'ThumbnailPrepared' ],
            'limit' => 20
        ]);

        $result->getTotal();

        $folderId = $config->get('statics.files.uploadDefaultFolder');

        foreach ($result as $article) {

            if (!$file = $files->fetchByUid($article->getUid('image'))) {

                d($article);

                $fileUrl = $article->getConfig('source.thumbnail');
                $url = 'http://www.johannesschule-meppen.de/aktuelles/xxx/' . $fileUrl;

                $nfile = new \Frootbox\Persistence\File([
                    'folderId' => $folderId,
                    'name' => basename($url),
                    'type' => 'image/jpeg',
                    'config' => [
                        'importUrl' => $url
                    ],
                    'uid' => $article->getUid('image')
                ]);

                $file = $files->insert($nfile);
            }


            $file->setDate($article->getDateStart());

            // Generate filename
            $name = strtolower($file->getName());
            $x = explode('.', $name);
            $extension = array_pop($x);
            $name = implode('-', $x);
            $name = preg_replace("/[^[:alnum:]]/u", '-', $name);
            $name = str_pad($file->getId(), 7, '0', STR_PAD_LEFT) . '-' . $name . '.' . $extension;


            $segment = 'uploads/' . substr($file->getDate(), 0, 7) . '/';
            $path = $config->get('filesRootFolder') . $segment;

            if (!file_exists($path)) {
                mkdir($path);
            }

            $target = $path . $name;


            // Obtain file
            $src = file_get_contents($file->getConfig('importUrl'));

            file_put_contents($target, $src);

            $file->setSize(filesize($target));
            $file->setPath($segment . $name);

            $file->save();

            $article->setState('ThumbnailDone');
            $article->save();
        }

        die("Teilimport fertig! Noch " . ($result->getTotal() - $result->getCount()) . ' Übrig.');


        /**
         *
         */
        $result = $articles->fetch([
            'where' => [ 'state' => 'New' ],
            'limit' => 1000
        ]);

        $folderId = $config->get('statics.files.uploadDefaultFolder');

        foreach ($result as $article) {

            if ($file = $files->fetchByUid($article->getUid('image'))) {
                continue;
            }

            if (empty($fileUrl = $article->getConfig('import.url'))) {

                d($article);
            }

            $url = 'https://www.kirche-dalum.de/backup/xxx/' . $fileUrl;

            $nfile = new \Frootbox\Persistence\File([
                'folderId' => $folderId,
                'name' => basename($url),
                'type' => 'image/jpeg',
                'config' => [
                    'importUrl' => $url
                ],
                'uid' => $article->getUid('image')
            ]);

            $files->insert($nfile);

            $article->setState('ThumbnailPrepared');
            $article->save();
        }

        d("Done");


        /**
         * Gather articles
         */
        $url = 'https://www.kirche-dalum.de/backup/index.php/wir-in-der-presse.html';
        $source = file_get_contents($url);

        preg_match_all('#<span class="sige_thumb"><a href="(.*?)" rel="lightbox#', $source, $matches);

        $loop = 0;

        foreach ($matches[1] as $url) {

            // Extract date
            if (!preg_match('#(.*?)([0-9]{2})[\.,\,]{1,}([0-9]{2,})[\.,\,]([0-9]{2,}).*?\.jpg$#', $url, $match)) {
                d('Mishit: ' . $url);
            }


            $dateString = $match[4] . '-' . (int) $match[3] . '-' . (int) $match[2];
            $date = new \Frootbox\Dates\Date($dateString);

            $title = str_replace('.jpg', '', basename($url));


            $articles->insert(new \Frootbox\Ext\Core\News\Persistence\Article([
                'pageId' => 75,
                'pluginId' => 80,
                'title' => $title,
                'dateStart' => $date->format('%Y-%m-%d'),
                'config' => [
                    'import' => [
                        'url' => $url
                    ]
                ],
                'state' => 'New'
            ]));

            ++$loop;
        }


        die("Importiert: " . $loop);


    }



        /**
     * 
     */
    public function faaAction (
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ) {
        
        
        
        
        /**
         * Create filled files
         */
        $result = $articles->fetch([
            'where' => [ 'state' => 'ThumbnailSetted2' ],
            'limit' => 30            
        ]);
        
        $total = $result->getTotal();
        $folderId = $config->get('statics.files.uploadDefaultFolder');
        
        foreach ($result as $article) {
                        
            if (!$file = $files->fetchByUid($article->getUid('image'))) {
                
                $fileUrl = $article->getConfig('source.thumbnail');
                $url = 'http://www.johannesschule-meppen.de/aktuelles/xxx/' . $fileUrl;
                
                $nfile = new \Frootbox\Persistence\File([
                    'folderId' => $folderId,
                    'name' => basename($url),
                    'type' => 'image/jpeg',
                    'config' => [
                        'importUrl' => $url
                    ],
                    'uid' => $article->getUid('image')                                        
                ]);
                                                    
                $file = $files->insert($nfile);            
            }
            
            $file->setDate($article->getDateStart());
            
            // Generate filename
            $name = strtolower($file->getName());
            $x = explode('.', $name);
            $extension = array_pop($x);
            $name = implode('-', $x);
            $name = preg_replace("/[^[:alnum:]]/u", '-', $name);
            $name = str_pad($file->getId(), 7, '0', STR_PAD_LEFT) . '-' . $name . '.' . $extension;
            
            
            $segment = 'uploads/' . substr($file->getDate(), 0, 7) . '/';
            $path = $config->get('filesRootFolder') . $segment;
                        
            if (!file_exists($path)) {
                mkdir($path);
            }
            
            $target = $path . $name;
            
            
            // Obtain file
            $src = file_get_contents($file->getConfig('importUrl'));
                                    
            file_put_contents($target, $src);
            
            $file->setSize(filesize($target));
            $file->setPath($segment . $name);
            
            $file->save();
            
            $article->setState('ThumbnailDone');
            $article->save();
        }
            
        die("FERTIG!! (Rest: " . $total);
        
        
        /**
         * 
         */
        $result = $articles->fetch([
            'where' => [ 'state' => 'ThumbnailSetted2' ],
            'limit' => 1           
        ]);
        
        $folderId = $config->get('statics.files.uploadDefaultFolder');
        
        foreach ($result as $article) {
            
            
            if (!empty($fileUrl = $article->getConfig('source.thumbnail'))) {
                
                $url = 'http://www.johannesschule-meppen.de/aktuelles/xxx/' . $fileUrl;
                
                $nfile = new \Frootbox\Persistence\File([
                    'folderId' => $folderId,
                    'name' => basename($url),
                    'type' => 'image/jpeg',
                    'config' => [
                        'importUrl' => $url
                    ],
                    'uid' => $article->getUid('image')                                        
                ]);
                                    
                $files->insert($nfile); 
            }
            
            $article->setState('ThumbnailPrepared');
            $article->save();
        }
        
        d("Done");
        
        
        
        /**
         * 
         */
        $result = $articles->fetch([
            'where' => [ 'state' => 'ThumbnailSetted' ],
            'limit' => 100000           
        ]);
        
        
        foreach ($result as $article) {
            
            $url = $article->getConfig('source.url');
            
            $source = file_get_contents($url);
            
            if (preg_match('#<div class="newsarticle">.*?<h2>.*?</h2>.*?<div class="teaser">.*?<a href="(.*?)" class="highslide".*?<div id="start">#is', $source, $match)) {
                
                $thumbUrl = $match[1];
            }
            elseif (preg_match('#<div class="smartie-thumbnail smartie-thumbnail-(right|left)">.*?<img src="(.*?)" alt=".*?" />.*?</div>#is', $source, $match)) {
                
                $thumbUrl = $match[2];
            }
            else {
                
                p($url);
                d($source);
            }
            
            $article->addConfig([
                'source' => [
                    'thumbnail' => $thumbUrl
                ]
            ]);
            
            
            $article->setState('ThumbnailSetted2');            
            $article->save();            
        }
        
        d("Done");
        
        exit;
        
        /**
         * Create articles
         */
        $json = file_get_contents($this->getPath() . 'presse.json');
        $data = json_decode($json, true);
        
        $loop = 0;
        
        foreach ($data as $index => $article) {
            
            $narticle = new \Frootbox\Ext\Core\News\Persistence\Article([
                'pageId' => 7,
                'pluginId' => 11,
                'title' => $article['title'],
                'date' => $article['isodate'],
                'dateStart' => $article['isodate'],
                'config' => [
                    'source' => $article
                ],
                'state' => 'Inserted'
            ]);
            
            $articles->insert($narticle);
            
            ++$loop;
        }
        
        die("Fertig: " . $loop);
        
        
        $json = '["pressespiegel/meppener-schueler-auf-sprachreise-bei-ihren-franzoesischen-freunden.html","pressespiegel/fleissige-helfer-sammeln-tonnenweise-muell.html","pressespiegel/meppener-schueler-in-englands-hauptstadt.html","pressespiegel/schueler-als-statisten.html","pressespiegel/schueler-lernen-den-unternehmensalltag-kennen.html","pressespiegel/niederlaendische-schueler-erkunden-meppen-per-stadtrallye.html","pressespiegel/kleine-alltagshelden-leisten-ganz-grosses.html","pressespiegel/schueler-praesentieren-viele-kleine-projekte-mit-grosser-wirkung.html","pressespiegel/spedition-unterstuetzt-bewerbungstraining.html","pressespiegel/foerderverein-spendet-an-frauenhaus.html","pressespiegel/schullogo-aus-kupferrohren.html","pressespiegel/werde-was-du-willst.html","pressespiegel/johannesschule-und-marianum-besuchen-elbphilharmonie.html","pressespiegel/schueler-schulen-die-senioren-an-ihren-computern.html","pressespiegel/jugendliche-bekommen-einblick-in-die-berufswelt.html","pressespiegel/in-vorweihnachtlicher-stimmung.html","pressespiegel/adventsweg-in-der-propsteikirche.html","pressespiegel/vom-weihnachtsmarkt-in-die-schule.html","pressespiegel/verkehrserziehungstag-fuer-schueler.html","pressespiegel/zivilcourage-ist-gefordert.html","pressespiegel/schueler-entdecken-den-forst.html","pressespiegel/meppener-besuchen-papst-franziskus.html","pressespiegel/meppener-schueler-erleben-ausbildung-in-der-praxis.html","pressespiegel/schueler-sammeln-3000-euro.html","pressespiegel/gut-ausgeruestet-geht-es-nun-nach-rom.html","pressespiegel/sprachkenntnis-bewiesen.html","pressespiegel/die-gluecklichen-machen-aus-allem-das-beste.html","pressespiegel/streifzuege-durch-london-und-seine-attraktionen.html","pressespiegel/den-schwarzen-clown-vom-guten-ueberzeugt.html","pressespiegel/austausch-sorgt-fuer-verstaendnis-der-kulturen.html","pressespiegel/demnaechst-strandaufsicht.html","pressespiegel/matheunterricht-ganz-lebensnah.html","pressespiegel/schueler-spenden.html","pressespiegel/aufpassen-aufnahme-laeuft.html","pressespiegel/woche-gegen-sucht.html","pressespiegel/neuer-didaktischer-leiter-an-der-johannesschule.html","pressespiegel/selbstbestimmung-und-verantwortung.html","pressespiegel/meppener-schueler-bauen-logo-aus-kupferrohren.html","pressespiegel/schueler-gestalten-krippe.html","pressespiegel/franzoesische-austauschschueler-im-rathaus-emfangen.html","pressespiegel/aktion-kinder-helfen-kinder.html","pressespiegel/fit-in-sachen-pc-und-internet.html","pressespiegel/gut-geruestet-fuer-die-zukunft.html","pressespiegel/sieg-fuer-monsterbeat.html","pressespiegel/kletternetz-spaceball-klarer-favorit.html","pressespiegel/projektwoche-zum-thema-schoepfungsgeschichte.html","pressespiegel/johannesschule-organisiert-die-bundestag-juniorwahl.html","pressespiegel/berufsorientierung-an-der-johannessschule.html","pressespiegel/eine-grosse-herrausforderung.html","pressespiegel/fuer-einen-guten-zweck-arbeiten.html","pressespiegel/absolventen-der-johannesschule-verabschiedet.html","pressespiegel/mach-doch-einfach.html","pressespiegel/gastfamilie-und-ausfluege.html","pressespiegel/johannesschueler-aus-kultur-trip-in-paris.html","pressespiegel/in-grossen-spruengen-zum-richtigen-ergebnis.html","pressespiegel/schueler-als-rettungsschwimmer-an-ostsee.html","pressespiegel/immer-noch-ein-grosses-projekt.html","pressespiegel/interesse-fuer-franzoesisch-wecken.html","pressespiegel/radiosendung-zur-meppener-kreuztracht.html","pressespiegel/die-schueler-stark-machen-fuers-leben.html","pressespiegel/seid-drei-jahrzehnten-abschlussfahrt-nach-inzell.html","pressespiegel/ein-preis-fuenf-gewinner.html","pressespiegel/15-neue-rettungsschwimmer.html","pressespiegel/jugendliche-auf-stellensuche.html","pressespiegel/schulleiter-zeigt-alten-jammerlappen.html","pressespiegel/vom-hinfallen-und-aufstehen.html","pressespiegel/johannesschule-positioniert-sich-neu.html","pressespiegel/deutsch-franzoesische-freundschaft.html","pressespiegel/2600-euro-fuer-den-tierschutz-in-meppen.html","pressespiegel/schueler-der-johannesschule-gestalten-adventsweg.html","pressespiegel/guetesiegel-fuer-sieben-emslaendische-schulen.html","pressespiegel/johannesschueler-packen-weihnachtspaeckchen.html","pressespiegel/weisse-eier-von-huehnern-mit-weissen-ohrscheiben.html","pressespiegel/geschickte-haende-und-spass-an-technik.html","pressespiegel/palatschinken-crepes-und-pancakes.html","pressespiegel/speiseplan-und-wetterstation.html","pressespiegel/team-der-johannesschule-ueberlegen.html","pressespiegel/sozialer-tag-bringt-2500-euro.html","pressespiegel/drei-schulen-auf-einem-weg.html","pressespiegel/einblicke-in-die-arbeitswelt.html","pressespiegel/beeindruckender-bandabend.html","pressespiegel/johannesschule-meppen-sorgt-fuer-alarm-im-hasetal.html","pressespiegel/johannesschule-besteht-seit-40-jahren-1.html","pressespiegel/raus-aus-totem-winkel.html","pressespiegel/andreas-niemeyer-verlaesst-die-johannesschule.html","pressespiegel/vier-junge-leute-ausgezeichnet.html","pressespiegel/wer-kann-gut-englisch.html","pressespiegel/lernen-in-einem-garten-voller-leben.html","pressespiegel/bandabend-zum-jubilaeum-der-johannesschule.html","pressespiegel/schueler-schulen-senioren.html","pressespiegel/meppener-schueler-bei-urauffuehrung.html","pressespiegel/viel-besser-rechnen-als-ein-kaenguru.html","pressespiegel/schulen-bauen-kooperation-aus.html","pressespiegel/johannesschueler-treffen-bundespraesident.html","pressespiegel/tom-beckering-ist-alltagheld-2015.html","pressespiegel/kontakt-wird-intensiviert.html","pressespiegel/das-kopftuch-ist-meine-entscheidung.html","pressespiegel/schufrei-wegen-defekter-heizung.html","pressespiegel/kinderrechte-ernst-nehmen.html","pressespiegel/pariser-schueler-erleben-advent-in-meppen.html","pressespiegel/weihnachtspyramide-fuer-johannesschule.html","pressespiegel/handgemachte-soundmaschinen.html","pressespiegel/meppener-schueler-spenden-fuer-waermestube-und-tafel.html","pressespiegel/schueler-zeigen-senioren-internet.html","pressespiegel/fahrendes-kulturinstitut.html","pressespiegel/sieben-cent-fuer-ein-mittagessen.html","pressespiegel/viel-geschick-und-schnelligkeit.html","pressespiegel/ein-ganz-schoenes-stueck-arbeit.html","pressespiegel/berufsausbildungsboerse-der-johannesschule.html","pressespiegel/neue-sprachklasse-der-johannesschule-meppen.html","pressespiegel/bruecke-der-freundschaft-erneuert.html","pressespiegel/jugend-denkt-ueber-die-zukunft-nach.html","pressespiegel/johannesschueler-bei-wettbewerb-erfolgreich.html","pressespiegel/nachhaltig-handeln.html","pressespiegel/18-schueler-entdecken-paris.html","pressespiegel/erstes-meppener-wallfest-startet-bei-strahlendem-sonnenschein.html","pressespiegel/der-funke-springt-ueber.html","pressespiegel/ganz-schoen-clever-geloest.html","pressespiegel/mobbing-transparent-machen.html","pressespiegel/schueler-starten-den-tag-gesund.html","pressespiegel/buergermeister-empfaengt-schueler-aus-emmen.html","pressespiegel/aktiv-am-weltwassertag.html","pressespiegel/johannesschule-erweitert-das-schulkonzept.html","pressespiegel/schueler-sensibilisieren-fuer-umgang-mit-geld.html","pressespiegel/spende-fuer-syrische-fluechtlingsfamilie.html","pressespiegel/einblicke-in-die-blechtechnik.html","pressespiegel/15-jahre-austausch-mit-paris.html","pressespiegel/dank-an-schueler-per-post-aus-ghana.html","pressespiegel/senioren-den-weg-ins-internet-ebnen.html","pressespiegel/schueler-sammeln-fuer-den-tierschutz.html","pressespiegel/franzoesische-austauschschueler-im-ratssaal.html","pressespiegel/sportliche-schule-in-meppen.html","pressespiegel/johannesschule-verkauft-weihnachtliches.html","pressespiegel/technisches-verstaendnis-und-handwerkliches-geschick.html","pressespiegel/computerkurs-fuer-senioren.html","pressespiegel/schueler-praesentieren-roboactvity.html","pressespiegel/lauf-fuer-kranke-kinder.html","pressespiegel/berufsinfotag-an-der-johannesschule.html","pressespiegel/kreativ-sein-macht-spass.html","pressespiegel/auf-uns-in-der-johannesschule-meppen.html","pressespiegel/zehn-schueler-der-johannesschule-bewachen-strand.html","pressespiegel/schueler-sammeln-rucksaecke-fuer-afrika.html","pressespiegel/wuerstchen-erhitzen-mit-dem-kuehlschrank.html","pressespiegel/meppener-schueler-bei-heiligsprechung-in-rom.html","pressespiegel/drei-passende-motive-fuer-die-kirchliche-schule.html","pressespiegel/niederlaendische-schueler-zu-gast-in-meppen.html","pressespiegel/der-fruchtige-weg-in-die-tabak-sucht.html","pressespiegel/freundschaftliche-zusammenarbeit.html","pressespiegel/schueler-fuer-das-leben-schulen.html","pressespiegel/deutsche-weihnachten-hautnah-erleben.html","pressespiegel/schaustellerkinder-an-der-johannesschule.html","pressespiegel/schueler-hoeren-von-not-in-armen-laendern.html","pressespiegel/adventskraenze-von-der-johannesschule.html","pressespiegel/erweiterte-leitung-an-der-johannesschule.html","pressespiegel/120-schueler-testen-ihre-technische-faehigkeiten.html","pressespiegel/schuelerarbeit-am-sozialen-tag.html","pressespiegel/junge-forscher-auf-neuen-wegen.html","pressespiegel/informationsboerse-ueber-ausbildungsberufe.html","pressespiegel/die-welt-der-technik-entdecken.html","pressespiegel/toter-winkel-birgt-grosse-gefahr.html","pressespiegel/austausch-foerdert-wertschaetzung.html","pressespiegel/in-der-zeltkirche-ins-gespraech-kommen.html","pressespiegel/johannesschule-gedenkt-papstes.html","pressespiegel/jugendliche-diskutieren-ueber-europaeische-union.html","pressespiegel/10750-euro-vom-foerderverein.html","pressespiegel/fuer-den-sozialpreis-nominiert.html","pressespiegel/kooperationsklasse-im-sekundarbereich-i.html","pressespiegel/memory-bleibt-in-erinnerung.html","pressespiegel/schueler-stark-machen-fuers-leben.html","pressespiegel/kanu-klettern-angeln-rudern-golf.html","pressespiegel/mit-der-eisbahn-wuerstchen-erwaermen.html","pressespiegel/internationale-woche-in-der-johannesschule.html","pressespiegel/neue-raeume-fuer-naturwissenschaften.html","pressespiegel/eltern-mit-tombola-wartezeit-verkuerzt.html","pressespiegel/rettungsschwimmer-beeindrucken-publikum.html","pressespiegel/schueler-fuer-publikumspreis-nominiert.html","pressespiegel/johannesschuelerinnen-setzen-sich-durch.html","pressespiegel/ich-erinnere-mich-ungern-an-meppen.html","pressespiegel/schuldenbremse-und-16-jaehrige-waehler.html","pressespiegel/schulbaenke-mit-arbeitsplatz-getauscht.html","pressespiegel/lernen-wie-im-labor.html","pressespiegel/landespolitikern-auf-den-zahn-gefuehlt.html","pressespiegel/hilfe-ich-verstehe-mein-kind-nicht.html","pressespiegel/toter-winkel-und-bremsweg.html","pressespiegel/unternehmen-informieren-schueler.html","pressespiegel/roboterprojekt-an-der-johannesschule.html","pressespiegel/erfolgreiche-franzoesisch-pruefung-an-der-johannesschule.html","pressespiegel/leere-pfandflaschen-sorgen-fuer-gesundes-trinkwasser-in-afrika.html","pressespiegel/kiwanis-club-zeichnet-schueler-aus.html","pressespiegel/dann-geht-der-himmel-auf.html","pressespiegel/junge-rettungsschwimmer-proben-ernstfall.html","pressespiegel/deutschland-niederlande.html","pressespiegel/farbkomet-bei-jedem-deutschlandtreffer.html","pressespiegel/jugendliche-informieren-sich-ueber-medienberufe.html","pressespiegel/pumpe-schont-ressourcen.html","pressespiegel/eine-eisbahn-macht-viele-wuerstchen-heiss.html","pressespiegel/von-der-flasche-zum-polyestershirt.html","pressespiegel/mehr-als-sonnenbraeune-und-strandspaziergang.html","pressespiegel/meppen-erstaunt-englische-schueler-aus-stockton-on-tees.html","pressespiegel/voneinander-und-miteinander-lernen.html","pressespiegel/schueler-der-johannesschule-bieten-ab-februar-sport-ag-an.html","pressespiegel/johannesschule-meppen-spendet-fuer-den-tierschutz.html","pressespiegel/die-klasse-r10c-siegte-beim-eisstockschiessen.html","pressespiegel/rockiges-und-besinnliches.html","pressespiegel/ca-unterstuetzt-projekte.html","pressespiegel/mechatronik-zum-ausprobieren.html","pressespiegel/dank-der-verkehrswacht.html","pressespiegel/leistungsgerechter-schulen.html","pressespiegel/johannesschule-bleibt-sportfreundlich.html","pressespiegel/schueler-arbeiten-fuer-guten-zweck.html","pressespiegel/ueber-berufe-informiert.html","pressespiegel/mensa-neu-verpachtet.html","pressespiegel/africa-alive-in-der-johannesschule-meppen.html","pressespiegel/haeppchenweise-zum-marathon.html","pressespiegel/babysitter-mit-zertifikat.html","pressespiegel/johannesschule-nimmt-messstation-in-betrieb.html","pressespiegel/eine-ganze-schule-in-bewegung.html","pressespiegel/fernseh-meteorologe-kommt.html","pressespiegel/fuer-bernhard-gruenberg-hoeren-die-traenen-nie-auf.html","pressespiegel/musikunterricht-mit-echten-rockmusikern-als-lehrer.html","pressespiegel/johannesschule-will-keinen-gymnasialen-zweig.html","pressespiegel/schueler-lesen-platt-in-der-tagespflege.html","pressespiegel/sprach-diplom-erlangt.html","pressespiegel/nachbarn-lernen-sich-kennen.html","pressespiegel/verlaesslichkeit-und-puenktlichkeit.html","pressespiegel/ein-hauch-europa-weht-in-der-johannesschule.html","pressespiegel/schule-ohne-rassismus.html","pressespiegel/kirche-muss-sich-bewegen.html","pressespiegel/hilfe-am-pc-kein-problem.html","pressespiegel/klimaschutzpreis-fuer-schulen-und-den-nabu.html","pressespiegel/alltag-aelterer-menschen-erleichtern.html","pressespiegel/eiskaltes-vergnuegen-fuer-30-mannschaften.html","pressespiegel/aktion-adventskranz.html","pressespiegel/guetesiegel-fuer-zwei-schulen.html","pressespiegel/auf-harry-potters-spuren.html","pressespiegel/meppener-erste-gaumlste-im-paumlpstlichen-pub-gp-2.html","pressespiegel/insel-der-ruhe-fuer-schueler-und-lehrer.html","pressespiegel/roboter-mit-schokoladenseite.html","pressespiegel/3350-euro-fuer-die-meppener-tafel-und-die-waermestube.html","pressespiegel/finger-und-stifte-ersetzen-die-kreide.html","pressespiegel/kids-rocken.html","pressespiegel/schule-und-dlrg-kooperieren.html","pressespiegel/schueler-der-tagesbildungsstaette-und-der-johannesschule-kooperieren.html","pressespiegel/reibungsloser-uebergang-vertraglich-besiegelt.html","pressespiegel/sportler-sind-vorbilder.html","pressespiegel/jugendliche-koennen-ihren-mitschuelern-helfen.html","pressespiegel/schueler-engagieren-sich-fuer-kinderschutz.html","pressespiegel/niederlaender-entdecken-meppen.html","pressespiegel/besser-auf-das-berufsleben-vorbereiten.html","pressespiegel/den-blick-schaerfen.html","pressespiegel/gemeinsamkeiten-und-unterschiede.html","pressespiegel/neue-sozialarbeiterin.html","pressespiegel/symbolischer-schluessel-fuer-den-neuen-leiter.html","pressespiegel/mensaeinweihung.html","pressespiegel/heute-gibt-es-nudeln.html","pressespiegel/johannesschule-startet-ganztagsunterricht.html","pressespiegel/2550-fuer-einen-guten-zweck.html","pressespiegel/suchtpraeventionstage.html","pressespiegel/junge-reporter-bei-meppens-buergermeister-jan-erik-bohling.html","pressespiegel/vorlesewettbewerb-2008-2009.html","pressespiegel/duftende-tannenzweige-fuer-den-adventskranz.html","pressespiegel/ueber-den-unterrricht-hinaus-sind-schueler-sportlich-aktiv.html","pressespiegel/abschlussklasse-pflanzt-saeulen-hainbuche.html"]';
        $json = '[{"url":"pressespiegel/meppener-schueler-auf-sprachreise-bei-ihren-franzoesischen-freunden.html","title":"20. Juni: Meppener Schüler auf Sprachreise bei ihren französischen Freunden"},{"url":"pressespiegel/fleissige-helfer-sammeln-tonnenweise-muell.html","title":"02. Mai: Fleißige Helfer sammeln tonnenweise Müll"},{"url":"pressespiegel/meppener-schueler-in-englands-hauptstadt.html","title":"04. April: Meppener Schüler in Englands Hauptstadt"},{"url":"pressespiegel/schueler-als-statisten.html","title":"02. April: Schüler als Statisten"},{"url":"pressespiegel/schueler-lernen-den-unternehmensalltag-kennen.html","title":"26. März: Schüler lernen den Unternehmensalltag kennen"},{"url":"pressespiegel/niederlaendische-schueler-erkunden-meppen-per-stadtrallye.html","title":"26. März: Niederländische Schüler erkunden Meppen per Stadtrallye"},{"url":"pressespiegel/kleine-alltagshelden-leisten-ganz-grosses.html","title":"21. März: Kleine Alltagshelden leisten ganz Großes"},{"url":"pressespiegel/schueler-praesentieren-viele-kleine-projekte-mit-grosser-wirkung.html","title":"20. März: Schüler präsentieren viele kleine Projekte mit großer Wirkung"},{"url":"pressespiegel/spedition-unterstuetzt-bewerbungstraining.html","title":"15. März: Spedition unterstützt Bewerbungstraining"},{"url":"pressespiegel/foerderverein-spendet-an-frauenhaus.html","title":"05. März: Förderverein spendet an Frauenhaus"},{"url":"pressespiegel/schullogo-aus-kupferrohren.html","title":"21. Februar: Schullogo aus Kupferrohren"},{"url":"pressespiegel/werde-was-du-willst.html","title":"13. Februar: \"Werde, was du willst!\""},{"url":"pressespiegel/johannesschule-und-marianum-besuchen-elbphilharmonie.html","title":"23. Januar: Johannesschule und Marianum besuchen Elbphilharmonie"},{"url":"pressespiegel/schueler-schulen-die-senioren-an-ihren-computern.html","title":"08. Januar: Schüler schulen die Senioren an ihren Computern"},{"url":"pressespiegel/jugendliche-bekommen-einblick-in-die-berufswelt.html","title":"20. Dezember: Jugendliche bekommen Einblick in die Berufswelt"},{"url":"pressespiegel/in-vorweihnachtlicher-stimmung.html","title":"19. Dezember: In vorweihnachtlicher Stimmung"},{"url":"pressespiegel/adventsweg-in-der-propsteikirche.html","title":"12. Dezember: Adventsweg in der Propsteikirche"},{"url":"pressespiegel/vom-weihnachtsmarkt-in-die-schule.html","title":"29. November: Vom Weihnachtsmarkt in die Schule"},{"url":"pressespiegel/verkehrserziehungstag-fuer-schueler.html","title":"29. Oktober: Verkehrserziehungstag für Schüler"},{"url":"pressespiegel/zivilcourage-ist-gefordert.html","title":"29. Oktober: Zivilcourage ist gefordert"},{"url":"pressespiegel/schueler-entdecken-den-forst.html","title":"22. Oktober: Schüler entdecken den Forst"},{"url":"pressespiegel/meppener-besuchen-papst-franziskus.html","title":"01. Oktober: Meppener besuchen Papst Franziskus"},{"url":"pressespiegel/meppener-schueler-erleben-ausbildung-in-der-praxis.html","title":"10. September: Meppener Schüler erleben Ausbildung in der Praxis"},{"url":"pressespiegel/schueler-sammeln-3000-euro.html","title":"29. August: Schüler sammeln 3000 Euro"},{"url":"pressespiegel/gut-ausgeruestet-geht-es-nun-nach-rom.html","title":"28. August: Gut ausgerüstet geht es nun nach Rom"},{"url":"pressespiegel/sprachkenntnis-bewiesen.html","title":"06. Juli: Sprachkenntnis bewiesen","head":"2017"},{"url":"pressespiegel/die-gluecklichen-machen-aus-allem-das-beste.html","title":"26. Juni: Die Glücklichen machen aus allem das Beste","head":"2017"},{"url":"pressespiegel/streifzuege-durch-london-und-seine-attraktionen.html","title":"06. Juni: Streifzüge durch London und seine Attraktionen","head":"2017"},{"url":"pressespiegel/den-schwarzen-clown-vom-guten-ueberzeugt.html","title":"23. Mai: Den schwarzen Clown vom Guten überzeugt","head":"2017"},{"url":"pressespiegel/austausch-sorgt-fuer-verstaendnis-der-kulturen.html","title":"15. Mai: Austausch sorgt für Verständnis der Kulturen","head":"2017"},{"url":"pressespiegel/demnaechst-strandaufsicht.html","title":"26. April: Demnächst Strandaufsicht","head":"2017"},{"url":"pressespiegel/matheunterricht-ganz-lebensnah.html","title":"16. April: Matheunterricht ganz lebensnah","head":"2017"},{"url":"pressespiegel/schueler-spenden.html","title":"03. April: Schüler spenden","head":"2017"},{"url":"pressespiegel/aufpassen-aufnahme-laeuft.html","title":"15. März: Aufpassen-Aufnahme läuft!","head":"2017"},{"url":"pressespiegel/woche-gegen-sucht.html","title":"02. März: Woche gegen Sucht","head":"2017"},{"url":"pressespiegel/neuer-didaktischer-leiter-an-der-johannesschule.html","title":"19. Februar: Neuer didaktischer Leiter an der Johannesschule","head":"2017"},{"url":"pressespiegel/selbstbestimmung-und-verantwortung.html","title":"13. Februar: Selbstbestimmung und Verantwortung","head":"2017"},{"url":"pressespiegel/meppener-schueler-bauen-logo-aus-kupferrohren.html","title":"30. Januar: Meppener Schüler bauen Logo aus Kupferrohren","head":"2017"},{"url":"pressespiegel/schueler-gestalten-krippe.html","title":"15. Dezember: Schüler gestalten Krippe","head":"2017"},{"url":"pressespiegel/franzoesische-austauschschueler-im-rathaus-emfangen.html","title":"05. Dezember: Französische Austauschschüler im Rathaus emfangen","head":"2017"},{"url":"pressespiegel/aktion-kinder-helfen-kinder.html","title":"27. November: Aktion Kinder helfen Kinder","head":"2017"},{"url":"pressespiegel/fit-in-sachen-pc-und-internet.html","title":"13. November: Fit in Sachen PC und Internet","head":"2017"},{"url":"pressespiegel/gut-geruestet-fuer-die-zukunft.html","title":"13. November: Gut gerüstet für die Zukunft","head":"2017"},{"url":"pressespiegel/sieg-fuer-monsterbeat.html","title":"06. November: Sieg für MonsterBeat","head":"2017"},{"url":"pressespiegel/kletternetz-spaceball-klarer-favorit.html","title":"29. September: Kletternetz Spaceball klarer Favorit","head":"2017"},{"url":"pressespiegel/projektwoche-zum-thema-schoepfungsgeschichte.html","title":"25. September: Projektwoche zum Thema Schöpfungsgeschichte","head":"2017"},{"url":"pressespiegel/johannesschule-organisiert-die-bundestag-juniorwahl.html","title":"21. September: Johannesschule organisiert die Bundestag-Juniorwahl","head":"2017"},{"url":"pressespiegel/berufsorientierung-an-der-johannessschule.html","title":"04. September: Berufsorientierung an der Johannessschule","head":"2017"},{"url":"pressespiegel/eine-grosse-herrausforderung.html","title":"24. August: Eine große Herrausforderung","head":"2017"},{"url":"pressespiegel/fuer-einen-guten-zweck-arbeiten.html","title":"21. August: Für einen guten Zweck arbeiten","head":"2017"},{"url":"pressespiegel/absolventen-der-johannesschule-verabschiedet.html","title":"23. Juni: Absolventen der Johannesschule verabschiedet","head":"2016"},{"url":"pressespiegel/mach-doch-einfach.html","title":"22. Juni: Mach doch einfach","head":"2016"},{"url":"pressespiegel/gastfamilie-und-ausfluege.html","title":"12. Juni: Gastfamilie und Ausflüge","head":"2016"},{"url":"pressespiegel/johannesschueler-aus-kultur-trip-in-paris.html","title":"01. Juni: Johannesschüler aus Kultur-Trip in Paris","head":"2016"},{"url":"pressespiegel/in-grossen-spruengen-zum-richtigen-ergebnis.html","title":"29. Mai: In grossen Sprüngen zum richtigen Ergebnis","head":"2016"},{"url":"pressespiegel/schueler-als-rettungsschwimmer-an-ostsee.html","title":"23. Mai: Schüler als Rettungsschwimmer an Ostsee","head":"2016"},{"url":"pressespiegel/immer-noch-ein-grosses-projekt.html","title":"22. Mai: Immer noch ein großes Projekt","head":"2016"},{"url":"pressespiegel/interesse-fuer-franzoesisch-wecken.html","title":"08. Mai: Interesse für Französisch wecken","head":"2016"},{"url":"pressespiegel/radiosendung-zur-meppener-kreuztracht.html","title":"18. April: Radiosendung zur Meppener Kreuztracht","head":"2016"},{"url":"pressespiegel/die-schueler-stark-machen-fuers-leben.html","title":"23. März: Die Schüler stark machen fürs Leben","head":"2016"},{"url":"pressespiegel/seid-drei-jahrzehnten-abschlussfahrt-nach-inzell.html","title":"20. März: Seid drei Jahrzehnten Abschlussfahrt nach Inzell","head":"2016"},{"url":"pressespiegel/ein-preis-fuenf-gewinner.html","title":"15. März: Ein Preis, fünf Gewinner","head":"2016"},{"url":"pressespiegel/15-neue-rettungsschwimmer.html","title":"23. Februar: 15 neue Rettungsschwimmer","head":"2016"},{"url":"pressespiegel/jugendliche-auf-stellensuche.html","title":"13. Februar: Jugendliche auf Stellensuche","head":"2016"},{"url":"pressespiegel/schulleiter-zeigt-alten-jammerlappen.html","title":"02. Februar: Schulleiter zeigt alten Jammerlappen","head":"2016"},{"url":"pressespiegel/vom-hinfallen-und-aufstehen.html","title":"02. Februar: Vom Hinfallen und Aufstehen","head":"2016"},{"url":"pressespiegel/johannesschule-positioniert-sich-neu.html","title":"19. Dezember: Johannesschule positioniert sich neu","head":"2016"},{"url":"pressespiegel/deutsch-franzoesische-freundschaft.html","title":"12. Dezember: Deutsch-Französische-Freundschaft","head":"2016"},{"url":"pressespiegel/2600-euro-fuer-den-tierschutz-in-meppen.html","title":"12. Dezember: 2600 Euro für den Tierschutz in Meppen","head":"2016"},{"url":"pressespiegel/schueler-der-johannesschule-gestalten-adventsweg.html","title":"09. Dezember: Schüler der Johannesschule gestalten Adventsweg","head":"2016"},{"url":"pressespiegel/guetesiegel-fuer-sieben-emslaendische-schulen.html","title":"28. November: Gütesiegel für sieben emsländische Schulen","head":"2016"},{"url":"pressespiegel/johannesschueler-packen-weihnachtspaeckchen.html","title":"11. November: Johannesschüler packen Weihnachtspäckchen","head":"2016"},{"url":"pressespiegel/weisse-eier-von-huehnern-mit-weissen-ohrscheiben.html","title":"07. November: Weiße Eier von Hühnern mit weißen Ohrscheiben","head":"2016"},{"url":"pressespiegel/geschickte-haende-und-spass-an-technik.html","title":"01. November: Geschickte Hände und Spaß an Technik","head":"2016"},{"url":"pressespiegel/palatschinken-crepes-und-pancakes.html","title":"25. Oktober: Palatschinken, Crepes und Pancakes","head":"2016"},{"url":"pressespiegel/speiseplan-und-wetterstation.html","title":"24. Oktober: Speiseplan und Wetterstation","head":"2016"},{"url":"pressespiegel/team-der-johannesschule-ueberlegen.html","title":"19. September: Team der Johannesschule überlegen","head":"2016"},{"url":"pressespiegel/sozialer-tag-bringt-2500-euro.html","title":"15. September: Sozialer Tag bringt 2500 Euro","head":"2016"},{"url":"pressespiegel/drei-schulen-auf-einem-weg.html","title":"09. September: Drei Schulen auf einem Weg","head":"2016"},{"url":"pressespiegel/einblicke-in-die-arbeitswelt.html","title":"05. September: Einblicke in die Arbeitswelt","head":"2016"},{"url":"pressespiegel/beeindruckender-bandabend.html","title":"23. August: Beeindruckender Bandabend","head":"2016"},{"url":"pressespiegel/johannesschule-meppen-sorgt-fuer-alarm-im-hasetal.html","title":"22. August: Johannesschule Meppen sorgt für Alarm im Hasetal","head":"2016"},{"url":"pressespiegel/johannesschule-besteht-seit-40-jahren-1.html","title":"16. August: Johannesschule besteht seit 40 Jahren","head":"2016"},{"url":"pressespiegel/raus-aus-totem-winkel.html","title":"11. August: Raus aus totem Winkel","head":"2016"},{"url":"pressespiegel/andreas-niemeyer-verlaesst-die-johannesschule.html","title":"11. August: Andreas Niemeyer verlässt die Johannesschule","head":"2016"},{"url":"pressespiegel/vier-junge-leute-ausgezeichnet.html","title":"22. Juni: Vier junge Leute ausgezeichnet","head":"2015"},{"url":"pressespiegel/wer-kann-gut-englisch.html","title":"20. Juni: Wer kann gut Englisch?","head":"2015"},{"url":"pressespiegel/lernen-in-einem-garten-voller-leben.html","title":"20. Juni: Lernen in einem Garten voller Leben","head":"2015"},{"url":"pressespiegel/bandabend-zum-jubilaeum-der-johannesschule.html","title":"15. Juni: Bandabend zum Jubiläum der Johannesschule","head":"2015"},{"url":"pressespiegel/schueler-schulen-senioren.html","title":"14. Juni: Schüler schulen Senioren","head":"2015"},{"url":"pressespiegel/meppener-schueler-bei-urauffuehrung.html","title":"30. Mai: Meppener Schüler bei Uraufführung","head":"2015"},{"url":"pressespiegel/viel-besser-rechnen-als-ein-kaenguru.html","title":"18. April: Viel besser rechnen als ein Känguru","head":"2015"},{"url":"pressespiegel/schulen-bauen-kooperation-aus.html","title":"29. März: Schulen bauen Kooperation aus","head":"2015"},{"url":"pressespiegel/johannesschueler-treffen-bundespraesident.html","title":"02. März: Johannesschüler treffen Bundespräsident","head":"2015"},{"url":"pressespiegel/tom-beckering-ist-alltagheld-2015.html","title":"25. Februar: Tom Beckering ist Alltagheld 2015","head":"2015"},{"url":"pressespiegel/kontakt-wird-intensiviert.html","title":"16. Februar: Kontakt wird intensiviert","head":"2015"},{"url":"pressespiegel/das-kopftuch-ist-meine-entscheidung.html","title":"02. Februar: Das Kopftuch ist meine Entscheidung","head":"2015"},{"url":"pressespiegel/schufrei-wegen-defekter-heizung.html","title":"19. Januar: Schufrei wegen defekter Heizung","head":"2015"},{"url":"pressespiegel/kinderrechte-ernst-nehmen.html","title":"06. Januar: Kinderrechte ernst nehmen","head":"2015"},{"url":"pressespiegel/pariser-schueler-erleben-advent-in-meppen.html","title":"02. Dezember: Pariser Schüler erleben Advent in Meppen","head":"2015"},{"url":"pressespiegel/weihnachtspyramide-fuer-johannesschule.html","title":"01. Dezember: Weihnachtspyramide für Johannesschule","head":"2015"},{"url":"pressespiegel/handgemachte-soundmaschinen.html","title":"26. November: Handgemachte Soundmaschinen","head":"2015"},{"url":"pressespiegel/meppener-schueler-spenden-fuer-waermestube-und-tafel.html","title":"16. November: Meppener Schüler spenden für Wärmestube und Tafel","head":"2015"},{"url":"pressespiegel/schueler-zeigen-senioren-internet.html","title":"05. November: Schüler zeigen Senioren Internet","head":"2015"},{"url":"pressespiegel/fahrendes-kulturinstitut.html","title":"04. November: Fahrendes Kulturinstitut","head":"2015"},{"url":"pressespiegel/sieben-cent-fuer-ein-mittagessen.html","title":"09. Oktober: Sieben Cent für ein Mittagessen","head":"2015"},{"url":"pressespiegel/viel-geschick-und-schnelligkeit.html","title":"07. Oktober: Viel Geschick und Schnelligkeit","head":"2015"},{"url":"pressespiegel/ein-ganz-schoenes-stueck-arbeit.html","title":"25. September: Ein ganz schönes Stück Arbeit","head":"2015"},{"url":"pressespiegel/berufsausbildungsboerse-der-johannesschule.html","title":"16. September: Berufsausbildungsbörse der Johannesschule","head":"2015"},{"url":"pressespiegel/neue-sprachklasse-der-johannesschule-meppen.html","title":"07. September: Neue Sprachklasse der Johannesschule Meppen","head":"2015"},{"url":"pressespiegel/bruecke-der-freundschaft-erneuert.html","title":"17. Juli: Brücke der Freundschaft erneuert","head":"2014"},{"url":"pressespiegel/jugend-denkt-ueber-die-zukunft-nach.html","title":"13. Juli: Jugend denkt über die Zukunft nach","head":"2014"},{"url":"pressespiegel/johannesschueler-bei-wettbewerb-erfolgreich.html","title":"06. Juli: Johannesschüler bei Wettbewerb erfolgreich","head":"2014"},{"url":"pressespiegel/nachhaltig-handeln.html","title":"30. Juni: Nachhaltig handeln","head":"2014"},{"url":"pressespiegel/18-schueler-entdecken-paris.html","title":"23. Juni: 18 Schüler entdecken Paris","head":"2014"},{"url":"pressespiegel/erstes-meppener-wallfest-startet-bei-strahlendem-sonnenschein.html","title":"15. Juni: Erstes Meppener Wallfest startet bei strahlendem Sonnenschein","head":"2014"},{"url":"pressespiegel/der-funke-springt-ueber.html","title":"03. Juni: Der Funke springt über","head":"2014"},{"url":"pressespiegel/ganz-schoen-clever-geloest.html","title":"22. Mai: Ganz schön clever gelöst","head":"2014"},{"url":"pressespiegel/mobbing-transparent-machen.html","title":"04. Mai: Mobbing transparent machen","head":"2014"},{"url":"pressespiegel/schueler-starten-den-tag-gesund.html","title":"04. Mai: Schüler starten den Tag Gesund","head":"2014"},{"url":"pressespiegel/buergermeister-empfaengt-schueler-aus-emmen.html","title":"28. April: Bürgermeister empfängt Schüler aus Emmen","head":"2014"},{"url":"pressespiegel/aktiv-am-weltwassertag.html","title":"23. März: Aktiv am Weltwassertag","head":"2014"},{"url":"pressespiegel/johannesschule-erweitert-das-schulkonzept.html","title":"16. März: Johannesschule erweitert das Schulkonzept","head":"2014"},{"url":"pressespiegel/schueler-sensibilisieren-fuer-umgang-mit-geld.html","title":"09. März: Schüler sensibilisieren für Umgang mit Geld","head":"2014"},{"url":"pressespiegel/spende-fuer-syrische-fluechtlingsfamilie.html","title":"02. März: Spende für syrische Flüchtlingsfamilie","head":"2014"},{"url":"pressespiegel/einblicke-in-die-blechtechnik.html","title":"27. Februar: Einblicke in die Blechtechnik","head":"2014"},{"url":"pressespiegel/15-jahre-austausch-mit-paris.html","title":"23. Januar: 15 Jahre Austausch mit Paris","head":"2014"},{"url":"pressespiegel/dank-an-schueler-per-post-aus-ghana.html","title":"16. Januar: Dank an Schüler per Post aus Ghana","head":"2014"},{"url":"pressespiegel/senioren-den-weg-ins-internet-ebnen.html","title":"05. Januar: Senioren den Weg ins Internet ebnen","head":"2014"},{"url":"pressespiegel/schueler-sammeln-fuer-den-tierschutz.html","title":"05. Januar: Schüler sammeln für den Tierschutz","head":"2014"},{"url":"pressespiegel/franzoesische-austauschschueler-im-ratssaal.html","title":"08. Dezember: Französische Austauschschüler im Ratssaal","head":"2014"},{"url":"pressespiegel/sportliche-schule-in-meppen.html","title":"08. Dezember: Sportliche Schule in Meppen","head":"2014"},{"url":"pressespiegel/johannesschule-verkauft-weihnachtliches.html","title":"18. November: Johannesschule verkauft Weihnachtliches","head":"2014"},{"url":"pressespiegel/technisches-verstaendnis-und-handwerkliches-geschick.html","title":"31. Oktober: Technisches Verständnis und handwerkliches Geschick","head":"2014"},{"url":"pressespiegel/computerkurs-fuer-senioren.html","title":"20. Oktober: Computerkurs für Senioren","head":"2014"},{"url":"pressespiegel/schueler-praesentieren-roboactvity.html","title":"07. Oktober: Schüler präsentieren RoboActvity","head":"2014"},{"url":"pressespiegel/lauf-fuer-kranke-kinder.html","title":"29. September: Lauf für kranke Kinder","head":"2014"},{"url":"pressespiegel/berufsinfotag-an-der-johannesschule.html","title":"22. September: Berufsinfotag an der Johannesschule","head":"2014"},{"url":"pressespiegel/kreativ-sein-macht-spass.html","title":"19. September: Kreativ sein macht Spaß","head":"2014"},{"url":"pressespiegel/auf-uns-in-der-johannesschule-meppen.html","title":"07. Juli: \"Auf uns\" in der Johannesschule Meppen","head":"2013"},{"url":"pressespiegel/zehn-schueler-der-johannesschule-bewachen-strand.html","title":"23. Juni: Zehn Schüler der Johannesschule bewachen Strand","head":"2013"},{"url":"pressespiegel/schueler-sammeln-rucksaecke-fuer-afrika.html","title":"23. Juni: Schüler sammeln Rucksäcke für Afrika","head":"2013"},{"url":"pressespiegel/wuerstchen-erhitzen-mit-dem-kuehlschrank.html","title":"23. Mai: Würstchen erhitzen mit dem Kühlschrank","head":"2013"},{"url":"pressespiegel/meppener-schueler-bei-heiligsprechung-in-rom.html","title":"05. Mai: Meppener Schüler bei Heiligsprechung in Rom","head":"2013"},{"url":"pressespiegel/drei-passende-motive-fuer-die-kirchliche-schule.html","title":"27. März: Drei passende Motive für die kirchliche Schule","head":"2013"},{"url":"pressespiegel/niederlaendische-schueler-zu-gast-in-meppen.html","title":"12. März: Niederländische Schüler zu Gast in Meppen","head":"2013"},{"url":"pressespiegel/der-fruchtige-weg-in-die-tabak-sucht.html","title":"06. März: Der fruchtige Weg in die Tabak-Sucht","head":"2013"},{"url":"pressespiegel/freundschaftliche-zusammenarbeit.html","title":"03. März: Freundschaftliche Zusammenarbeit","head":"2013"},{"url":"pressespiegel/schueler-fuer-das-leben-schulen.html","title":"05. Februar: Schüler für das Leben schulen","head":"2013"},{"url":"pressespiegel/deutsche-weihnachten-hautnah-erleben.html","title":"21. Dezember: Deutsche Weihnachten hautnah erleben","head":"2013"},{"url":"pressespiegel/schaustellerkinder-an-der-johannesschule.html","title":"11. Dezember: Schaustellerkinder an der Johannesschule","head":"2013"},{"url":"pressespiegel/schueler-hoeren-von-not-in-armen-laendern.html","title":"05. Dezember: Schüler hören von Not in armen Ländern","head":"2013"},{"url":"pressespiegel/adventskraenze-von-der-johannesschule.html","title":"20. November: Adventskränze von der Johannesschule","head":"2013"},{"url":"pressespiegel/erweiterte-leitung-an-der-johannesschule.html","title":"04. November: Erweiterte Leitung an der Johannesschule","head":"2013"},{"url":"pressespiegel/120-schueler-testen-ihre-technische-faehigkeiten.html","title":"26. September: 120 Schüler testen Ihre technische Fähigkeiten","head":"2013"},{"url":"pressespiegel/schuelerarbeit-am-sozialen-tag.html","title":"11. September: Schülerarbeit am Sozialen Tag","head":"2013"},{"url":"pressespiegel/junge-forscher-auf-neuen-wegen.html","title":"06. September: Junge Forscher auf neuen Wegen","head":"2013"},{"url":"pressespiegel/informationsboerse-ueber-ausbildungsberufe.html","title":"27. August: Informationsbörse über Ausbildungsberufe","head":"2013"},{"url":"pressespiegel/die-welt-der-technik-entdecken.html","title":"23. August: Die Welt der Technik entdecken","head":"2013"},{"url":"pressespiegel/toter-winkel-birgt-grosse-gefahr.html","title":"21. August: Toter Winkel birgt große Gefahr","head":"2013"},{"url":"pressespiegel/austausch-foerdert-wertschaetzung.html","title":"05. August: Austausch fördert Wertschätzung","head":"2013"},{"url":"pressespiegel/in-der-zeltkirche-ins-gespraech-kommen.html","title":"05. Juli: In der Zeltkirche ins Gespräch kommen","head":"2012"},{"url":"pressespiegel/johannesschule-gedenkt-papstes.html","title":"05. Juni: Johannesschule gedenkt Papstes","head":"2012"},{"url":"pressespiegel/jugendliche-diskutieren-ueber-europaeische-union.html","title":"21. Mai: Jugendliche diskutieren über Europäische Union","head":"2012"},{"url":"pressespiegel/10750-euro-vom-foerderverein.html","title":"26. April: 10750 Euro vom Förderverein","head":"2012"},{"url":"pressespiegel/fuer-den-sozialpreis-nominiert.html","title":"24. April: Für den Sozialpreis nominiert","head":"2012"},{"url":"pressespiegel/kooperationsklasse-im-sekundarbereich-i.html","title":"25. März: Kooperationsklasse im Sekundarbereich I","head":"2012"},{"url":"pressespiegel/memory-bleibt-in-erinnerung.html","title":"12. März: \"Memory\" bleibt in Erinnerung","head":"2012"},{"url":"pressespiegel/schueler-stark-machen-fuers-leben.html","title":"04. März: Schüler stark machen für\'s Leben","head":"2012"},{"url":"pressespiegel/kanu-klettern-angeln-rudern-golf.html","title":"22. Februar: Kanu, Klettern, Angeln, Rudern, Golf","head":"2012"},{"url":"pressespiegel/mit-der-eisbahn-wuerstchen-erwaermen.html","title":"12. Februar: Mit der Eisbahn Würstchen erwärmen","head":"2012"},{"url":"pressespiegel/internationale-woche-in-der-johannesschule.html","title":"08. Februar: Internationale Woche in der Johannesschule","head":"2012"},{"url":"pressespiegel/neue-raeume-fuer-naturwissenschaften.html","title":"07. Januar: Neue Räume für Naturwissenschaften","head":"2012"},{"url":"pressespiegel/eltern-mit-tombola-wartezeit-verkuerzt.html","title":"07. Januar: Eltern mit Tombola Wartezeit verkürzt","head":"2012"},{"url":"pressespiegel/rettungsschwimmer-beeindrucken-publikum.html","title":"03. Dezember: Rettungsschwimmer beeindrucken Publikum","head":"2012"},{"url":"pressespiegel/schueler-fuer-publikumspreis-nominiert.html","title":"15. November: Schüler für Publikumspreis nominiert","head":"2012"},{"url":"pressespiegel/johannesschuelerinnen-setzen-sich-durch.html","title":"05. November: Johannesschülerinnen setzen sich durch","head":"2012"},{"url":"pressespiegel/ich-erinnere-mich-ungern-an-meppen.html","title":"11. Oktober: Ich erinnere mich ungern an Meppen.","head":"2012"},{"url":"pressespiegel/schuldenbremse-und-16-jaehrige-waehler.html","title":"11. Oktober: Schuldenbremse und 16-jährige Wähler","head":"2012"},{"url":"pressespiegel/schulbaenke-mit-arbeitsplatz-getauscht.html","title":"05. Oktober: Schulbänke mit Arbeitsplatz getauscht","head":"2012"},{"url":"pressespiegel/lernen-wie-im-labor.html","title":"28. September: Lernen wie im Labor","head":"2012"},{"url":"pressespiegel/landespolitikern-auf-den-zahn-gefuehlt.html","title":"26. September: Landespolitikern auf den Zahn gefühlt","head":"2012"},{"url":"pressespiegel/hilfe-ich-verstehe-mein-kind-nicht.html","title":"14. September: Hilfe, ich verstehe mein Kind nicht","head":"2012"},{"url":"pressespiegel/toter-winkel-und-bremsweg.html","title":"10. September: Toter Winkel und Bremsweg","head":"2012"},{"url":"pressespiegel/unternehmen-informieren-schueler.html","title":"10. September: Unternehmen informieren Schüler","head":"2012"},{"url":"pressespiegel/roboterprojekt-an-der-johannesschule.html","title":"23. Juli: Roboterprojekt an der Johannesschule","head":"2011"},{"url":"pressespiegel/erfolgreiche-franzoesisch-pruefung-an-der-johannesschule.html","title":"18. Juli: Erfolgreiche Französisch-Prüfung an der Johannesschule","head":"2011"},{"url":"pressespiegel/leere-pfandflaschen-sorgen-fuer-gesundes-trinkwasser-in-afrika.html","title":"12. Juli: Leere Pfandflaschen sorgen für gesundes Trinkwasser in Afrika","head":"2011"},{"url":"pressespiegel/kiwanis-club-zeichnet-schueler-aus.html","title":"09. Juli: Kiwanis-Club zeichnet Schüler aus","head":"2011"},{"url":"pressespiegel/dann-geht-der-himmel-auf.html","title":"05. Juli: Dann geht der Himmel auf","head":"2011"},{"url":"pressespiegel/junge-rettungsschwimmer-proben-ernstfall.html","title":"22. Juni: Junge Rettungsschwimmer proben Ernstfall","head":"2011"},{"url":"pressespiegel/deutschland-niederlande.html","title":"16. Juni: Deutschland - Niederlande","head":"2011"},{"url":"pressespiegel/farbkomet-bei-jedem-deutschlandtreffer.html","title":"05. Juni: Farbkomet bei jedem Deutschlandtreffer","head":"2011"},{"url":"pressespiegel/jugendliche-informieren-sich-ueber-medienberufe.html","title":"11. Mai: Jugendliche informieren sich über Medienberufe","head":"2011"},{"url":"pressespiegel/pumpe-schont-ressourcen.html","title":"10. Mai: Pumpe schont Ressourcen","head":"2011"},{"url":"pressespiegel/eine-eisbahn-macht-viele-wuerstchen-heiss.html","title":"02. Mai: Eine Eisbahn macht viele Würstchen heiß","head":"2011"},{"url":"pressespiegel/von-der-flasche-zum-polyestershirt.html","title":"23. März: Von der Flasche zum Polyestershirt","head":"2011"},{"url":"pressespiegel/mehr-als-sonnenbraeune-und-strandspaziergang.html","title":"21. März: Mehr als Sonnenbräune und Strandspaziergang","head":"2011"},{"url":"pressespiegel/meppen-erstaunt-englische-schueler-aus-stockton-on-tees.html","title":"13. Februar: Meppen erstaunt englische Schüler aus Stockton on Tees","head":"2011"},{"url":"pressespiegel/voneinander-und-miteinander-lernen.html","title":"09. Februar: Voneinander und miteinander lernen","head":"2011"},{"url":"pressespiegel/schueler-der-johannesschule-bieten-ab-februar-sport-ag-an.html","title":"25. Januar: Schüler der Johannesschule bieten ab Februar Sport-AG an","head":"2011"},{"url":"pressespiegel/johannesschule-meppen-spendet-fuer-den-tierschutz.html","title":"25. Januar: Johannesschule Meppen spendet für den Tierschutz","head":"2011"},{"url":"pressespiegel/die-klasse-r10c-siegte-beim-eisstockschiessen.html","title":"11. Januar: Die Klasse R10C siegte beim Eisstockschießen","head":"2011"},{"url":"pressespiegel/rockiges-und-besinnliches.html","title":"19. Dezember: Rockiges und Besinnliches","head":"2011"},{"url":"pressespiegel/ca-unterstuetzt-projekte.html","title":"05. Dezember: C&amp;A unterstützt Projekte","head":"2011"},{"url":"pressespiegel/mechatronik-zum-ausprobieren.html","title":"22. November: Mechatronik zum Ausprobieren","head":"2011"},{"url":"pressespiegel/dank-der-verkehrswacht.html","title":"18. November: Dank der Verkehrswacht","head":"2011"},{"url":"pressespiegel/leistungsgerechter-schulen.html","title":"14. November: Leistungsgerechter schulen","head":"2011"},{"url":"pressespiegel/johannesschule-bleibt-sportfreundlich.html","title":"16. Oktober: Johannesschule bleibt sportfreundlich","head":"2011"},{"url":"pressespiegel/schueler-arbeiten-fuer-guten-zweck.html","title":"26. September: Schüler arbeiten für guten Zweck","head":"2011"},{"url":"pressespiegel/ueber-berufe-informiert.html","title":"31. August: Über Berufe informiert","head":"2011"},{"url":"pressespiegel/mensa-neu-verpachtet.html","title":"22. August: Mensa neu verpachtet","head":"2011"},{"url":"pressespiegel/africa-alive-in-der-johannesschule-meppen.html","title":"01. Juli: Africa Alive in der Johannesschule Meppen","head":"2010"},{"url":"pressespiegel/haeppchenweise-zum-marathon.html","title":"28. Juni: Häppchenweise zum Marathon","head":"2010"},{"url":"pressespiegel/babysitter-mit-zertifikat.html","title":"19. Juni: Babysitter mit Zertifikat","head":"2010"},{"url":"pressespiegel/johannesschule-nimmt-messstation-in-betrieb.html","title":"16. Juni: Johannesschule nimmt Messstation in Betrieb","head":"2010"},{"url":"pressespiegel/eine-ganze-schule-in-bewegung.html","title":"28. Mai: Eine ganze Schule in Bewegung","head":"2010"},{"url":"pressespiegel/fernseh-meteorologe-kommt.html","title":"28. Mai: Fernseh-Meteorologe kommt","head":"2010"},{"url":"pressespiegel/fuer-bernhard-gruenberg-hoeren-die-traenen-nie-auf.html","title":"21. Mai: Für Bernhard Grünberg hören die Tränen nie auf","head":"2010"},{"url":"pressespiegel/musikunterricht-mit-echten-rockmusikern-als-lehrer.html","title":"26. April: Musikunterricht mit echten Rockmusikern als Lehrer","head":"2010"},{"url":"pressespiegel/johannesschule-will-keinen-gymnasialen-zweig.html","title":"13. April: Johannesschule will keinen gymnasialen Zweig","head":"2010"},{"url":"pressespiegel/schueler-lesen-platt-in-der-tagespflege.html","title":"03. April: Schüler lesen platt in der Tagespflege","head":"2010"},{"url":"pressespiegel/sprach-diplom-erlangt.html","title":"03. April: Sprach-Diplom erlangt","head":"2010"},{"url":"pressespiegel/nachbarn-lernen-sich-kennen.html","title":"22. März: Nachbarn lernen sich kennen","head":"2010"},{"url":"pressespiegel/verlaesslichkeit-und-puenktlichkeit.html","title":"14. Februar: Verlässlichkeit und Pünktlichkeit","head":"2010"},{"url":"pressespiegel/ein-hauch-europa-weht-in-der-johannesschule.html","title":"08. Februar: Ein Hauch Europa weht in der Johannesschule","head":"2010"},{"url":"pressespiegel/schule-ohne-rassismus.html","title":"07. Februar: Schule ohne Rassismus","head":"2010"},{"url":"pressespiegel/kirche-muss-sich-bewegen.html","title":"07. Februar: Kirche muss sich bewegen","head":"2010"},{"url":"pressespiegel/hilfe-am-pc-kein-problem.html","title":"01. Februar: Hilfe am PC? Kein Problem","head":"2010"},{"url":"pressespiegel/klimaschutzpreis-fuer-schulen-und-den-nabu.html","title":"27. Januar: Klimaschutzpreis für Schulen und den NABU","head":"2010"},{"url":"pressespiegel/alltag-aelterer-menschen-erleichtern.html","title":"08. Januar: Alltag älterer Menschen erleichtern","head":"2010"},{"url":"pressespiegel/eiskaltes-vergnuegen-fuer-30-mannschaften.html","title":"15. Dezember: Eiskaltes Vergnügen für 30 Mannschaften","head":"2010"},{"url":"pressespiegel/aktion-adventskranz.html","title":"27. November: Aktion Adventskranz","head":"2010"},{"url":"pressespiegel/guetesiegel-fuer-zwei-schulen.html","title":"20. November: Gütesiegel für zwei Schulen","head":"2010"},{"url":"pressespiegel/auf-harry-potters-spuren.html","title":"30. Oktober: Auf Harry Potters Spuren","head":"2010"},{"url":"pressespiegel/meppener-erste-gaumlste-im-paumlpstlichen-pub-gp-2.html","title":"27. Oktober: Meppener erste Gäste im päpstlichen \"Pub GP 2\"","head":"2010"},{"url":"pressespiegel/insel-der-ruhe-fuer-schueler-und-lehrer.html","title":"12. Oktober: Insel der Ruhe für Schüler und Lehrer","head":"2010"},{"url":"pressespiegel/roboter-mit-schokoladenseite.html","title":"06. September: Roboter mit Schokoladenseite","head":"2010"},{"url":"pressespiegel/3350-euro-fuer-die-meppener-tafel-und-die-waermestube.html","title":"20. August: 3350 Euro für die Meppener Tafel und die Wärmestube","head":"2010"},{"url":"pressespiegel/finger-und-stifte-ersetzen-die-kreide.html","title":"09. August: Finger und Stifte ersetzen die Kreide","head":"2010"},{"url":"pressespiegel/kids-rocken.html","title":"29. Juli: Kids rocken","head":"2009"},{"url":"pressespiegel/schule-und-dlrg-kooperieren.html","title":"21. Juni: Schule und DLRG kooperieren","head":"2009"},{"url":"pressespiegel/schueler-der-tagesbildungsstaette-und-der-johannesschule-kooperieren.html","title":"16. Juni: Schüler der Tagesbildungsstätte und der Johannesschule kooperieren","head":"2009"},{"url":"pressespiegel/reibungsloser-uebergang-vertraglich-besiegelt.html","title":"26. Mai: Reibungsloser Übergang vertraglich besiegelt","head":"2009"},{"url":"pressespiegel/sportler-sind-vorbilder.html","title":"29. April: Sportler sind Vorbilder","head":"2009"},{"url":"pressespiegel/jugendliche-koennen-ihren-mitschuelern-helfen.html","title":"22. April: Jugendliche können ihren Mitschülern helfen","head":"2009"},{"url":"pressespiegel/schueler-engagieren-sich-fuer-kinderschutz.html","title":"29. März: Schüler engagieren sich für Kinderschutz","head":"2009"},{"url":"pressespiegel/niederlaender-entdecken-meppen.html","title":"17. März: Niederländer entdecken Meppen","head":"2009"},{"url":"pressespiegel/besser-auf-das-berufsleben-vorbereiten.html","title":"15. März: Besser auf das Berufsleben vorbereiten","head":"2009"},{"url":"pressespiegel/den-blick-schaerfen.html","title":"05. März: Den Blick schärfen","head":"2009"},{"url":"pressespiegel/gemeinsamkeiten-und-unterschiede.html","title":"08. Februar: Gemeinsamkeiten und Unterschiede","head":"2009"},{"url":"pressespiegel/neue-sozialarbeiterin.html","title":"07. Januar: Neue Sozialarbeiterin","head":"2009"},{"url":"pressespiegel/symbolischer-schluessel-fuer-den-neuen-leiter.html","title":"18. November: Symbolischer Schlüssel für den neuen Leiter","head":"2009"},{"url":"pressespiegel/mensaeinweihung.html","title":"04. November: Mensaeinweihung","head":"2009"},{"url":"pressespiegel/heute-gibt-es-nudeln.html","title":"26. Oktober: Heute gibt es Nudeln","head":"2009"},{"url":"pressespiegel/johannesschule-startet-ganztagsunterricht.html","title":"10. September: Johannesschule startet Ganztagsunterricht","head":"2009"},{"url":"pressespiegel/2550-fuer-einen-guten-zweck.html","title":"26. August: 2550€ für einen guten Zweck","head":"2009"},{"url":"pressespiegel/suchtpraeventionstage.html","title":"15. Februar: Suchtpräventionstage","head":"2008"},{"url":"pressespiegel/junge-reporter-bei-meppens-buergermeister-jan-erik-bohling.html","title":"10. Februar: Junge Reporter bei Meppens Bürgermeister Jan Erik Bohling","head":"2008"},{"url":"pressespiegel/vorlesewettbewerb-2008-2009.html","title":"23. Januar: Vorlesewettbewerb 2008/2009","head":"2008"},{"url":"pressespiegel/duftende-tannenzweige-fuer-den-adventskranz.html","title":"07. Dezember: Duftende Tannenzweige für den Adventskranz","head":"2008"},{"url":"pressespiegel/ueber-den-unterrricht-hinaus-sind-schueler-sportlich-aktiv.html","title":"04. November: Über den Unterrricht hinaus sind Schüler sportlich aktiv","head":"2008"},{"url":"pressespiegel/abschlussklasse-pflanzt-saeulen-hainbuche.html","title":"20. September: Abschlussklasse pflanzt Säulen-Hainbuche","head":"2008"}]';
        
        $links = json_decode($json, true);
                  
        $data = [ ];
        
        $tr = [
            ' Januar ' => '01.',
            ' Februar ' => '02.',
            ' März ' => '03.',
            ' April ' => '04.',
            ' Mai ' => '05.',
            ' Juni ' => '06.',
            ' Juli ' => '07.',
            ' August ' => '08.',
            ' September ' => '09.',
            ' Oktober ' => '10.',
            ' November ' => '11.',
            ' Dezember ' => '12.'
        ];
        
        foreach ($links as $link) {
            
            $year = $link['head'] ?? 2018;
            
            $da = explode(':', $link['title']);
            
            $date = trim(array_shift($da) . ' ' . $year);
            $date = strtr($date, $tr);
            $title = trim(implode(':', $da));
            
                      
            $data[] = [
                'url' => 'http://www.johannesschule-meppen.de/aktuelles/' . $link['url'],
                'title' => $title,
                'year' => $year,
                'date' => $date,
                'isodate' => (new \Frootbox\Dates\Date($date))->format('%Y-%m-%d')
            ];                   
        }
        
        
        http_response_code(200);        
        header('Content-disposition: attachment; filename=presse.json');
        header('Content-type: application/json');
        die(json_encode($data));
        
        
        /**
         * Optimize texts
         */
        $result = $articles->fetch([
            'where' => [ 'state' => 'TextDone' ],
            'limit' => 200            
        ]);
        
        foreach ($result as $article) {
            
            $text = $texts->fetchByUid($article->getUid('teaser'));
            
            $string = $text->getText();
            
            $ntext = wordwrap($string, 160, '$$$$');
            
            $da = explode('$$$$', $ntext);
            
            $text->setText(array_shift($da) . ' ...</p>');
            $text->save();
            
            $xtext = $texts->fetchByUid($article->getUid('text'), [
                'createOnMiss' => true
            ]);
            
            $xtext->setText('<p>... ' . implode(' ', $da) . '</p>');
            $xtext->save();
            
            $article->setState('TextReallyDone');
            $article->save();
            
            
        }
        
        
        exit;
        
        
        /**
         * Fetch texts
         */
        $result = $articles->fetch([
            'where' => [ 'state' => 'ThumbnailDones' ],
            'limit' => 100            
        ]);
        
        foreach ($result as $article) {
                        
            $source = file_get_contents($article->getConfig('source.url'));
            $da = explode('<div id="start">', explode('<div class="teaser">', $source)[1])[0];
                        
            // Strip caption
            $text = preg_replace('#<div class="caption"(.*?)</div>#is', '', $da);
            
            $text = preg_replace('#<div class="smartie-thumbnail(.*?)</div>#is', '', $text);

            $text = trim(strip_tags($text));
            $text = preg_replace('/\s+/', ' ', $text);
            
            $text = '<p>' . $text . '</p>';
                        
            $ntext = new \Frootbox\Persistence\Content\Text([
                'userId' => '{userId}',
                'uid' => $article->getUid('teaser'),
                'text' => $text
            ]);
                        
            $ntext = $texts->insert($ntext);
            
            
            $article->setState('TextDone');
            $article->save();
        }
        
        d("FÄTIG!");
        
        
        /**
         * Create filled files
         */
        $result = $articles->fetch([
            'where' => [ 'state' => 'ThumbnailReady' ],
            'limit' => 100            
        ]);
        
        $total = $result->getTotal();
        
        foreach ($result as $article) {
            
            $file = $files->fetchByUid($article->getUid('image'));
            $file->setDate($article->getDateStart());
            
            
            // Generate filename
            $name = strtolower($file->getName());
            $x = explode('.', $name);
            $extension = array_pop($x);
            $name = implode('-', $x);
            $name = preg_replace("/[^[:alnum:]]/u", '-', $name);
            $name = str_pad($file->getId(), 7, '0', STR_PAD_LEFT) . '-' . $name . '.' . $extension;
            
            
            $segment = 'uploads/' . substr($file->getDate(), 0, 7) . '/';
            $path = $config->get('filesRootFolder') . $segment;
                        
            if (!file_exists($path)) {
                mkdir($path);
            }
            
            $target = $path . $name;
                        
            // Obtain file
            $src = file_get_contents($file->getConfig('importUrl'));
                        
            file_put_contents($target, $src);
            
            $file->setSize(filesize($target));
            $file->setPath($segment . $name);
            
            $file->save();
            
            $article->setState('ThumbnailDones');
            $article->save();
        }
            
        die("FERTIG!! (Rest: " . $total);
        
        /**
         * Create empty files
         */
        $result = $articles->fetch([
            'where' => [ 'state' => 'New' ],
            'limit' => 50            
        ]);
        
        $folderId = $config->get('statics.files.uploadDefaultFolder');
        
        foreach ($result as $article) {
            
            if (!empty($file = $article->getConfig('source.thumbnail')[0])) {
                
                $url = 'http://www.johannesschule-meppen.de/aktuelles/' . $file['url'];
                       
                
                $nfile = new \Frootbox\Persistence\File([
                    'folderId' => $folderId,
                    'name' => basename($url),
                    'type' => 'image/jpeg',
                    'config' => [
                        'importUrl' => $url
                    ],
                    'uid' => 'Frootbox-Ext-Core-News-Persistence-Repositories-Articles:' . $article->getId() . ':image'                                        
                ]);
                                
                $files->insert($nfile);                
                    
            }
            
            $article->setState('ThumbnailReady');
            $article->save();
        }
        
        
        die("FERTIG!");
        
          
        
        /**
         * Create articles
         */
        $json = file_get_contents($this->getPath() . 'newsWithThumbnails.json');
        $data = json_decode($json, true);
        
        foreach ($data as $index => $news) {
            
            $article = new \Frootbox\Ext\Core\News\Persistence\Article([
                'dateStart' => $news['date'],
                'pageId' => 2,
                'pluginId' => 3,
                'title' => $news['title'],
                'state' => 'New',
                'config' => [
                    'source' => $news
                ]                
            ]);
            
            $articles->insert($article);           
        }
        
        d("OK!");
        
        exit;
        
        /**
         * Create empty files
         */
        $json = file_get_contents($this->getPath() . 'newsWithUrls.json');
        $data = json_decode($json, true);
        
        $loop = 0;
        
        foreach ($data as $index => $news) {
            
            $source = file_get_contents($news['url']);
            $da = explode('<div id="start">', explode('<div class="teaser">', $source)[1])[0];
            
            $news['thumbnail'] = [ ];
            
            // Extract image
            if (preg_match('#<div class="smartie-thumbnail smartie-thumbnail-(right|block)">.*?<a href="(.*?)" class="highslide" id=".*?" onclick="return hs\.expand\(this\)">.*?<div class="caption".*?>(.*?)</div>#is', $da, $match)) {
                                
                $news['thumbnail'][] = [
                    'url' => $match[2],
                    'caption' => trim(str_replace('vergrößern', '', strip_tags($match[3])))
                ];
            }
            elseif (preg_match('#<div class="smartie-thumbnail smartie-thumbnail-(right|block)">.*?<a href="(.*?)" class="highslide" id=".*?" onclick="return hs\.expand\(this\)">.*?<div class="caption"#is', $da, $match)) {
            
                $news['thumbnail'][] = [
                    'url' => $match[2],
                    'caption' => null
                ];
            }
            elseif (preg_match('#<div class="smartie-gallery.*?<ul>(.*?)</ul>#is', $da, $match)) {
                
                preg_match_all('#<a href="(.*?)"#', $match[1], $matches);
                
                foreach ($matches[1] as $url) {
                    $news['thumbnail'][] = [
                        'url' => $url,
                        'caption' => null
                    ];
                }
            }            
            elseif (preg_match('#<div class="smartie-thumbnail smartie-thumbnail-(right)">.*?<img src="(.*?)".*?<div class="caption".*?>(.*?)</div>#is', $da, $match)) {
                
                $news['thumbnail'][] = [
                    'url' => $match[2],
                    'caption' => trim($match[2])
                ];
            }
            else {
                
            }
            
            $data[$index] = $news;
            
            if (++$loop >= 100) {
              //  break;
            }
        }
        
        http_response_code(200);        
        header('Content-disposition: attachment; filename=newsWithThumbnails.json');
        header('Content-type: application/json');
        die(json_encode($data));
        
        
        /**
         * Gahter article links 
         */
        $list = '["aktuelles-action%5B8%5D=archive&page=1.html","aktuelles-action%5B8%5D=archive&page=3.html","aktuelles-action%5B8%5D=archive&page=4.html","aktuelles-action%5B8%5D=archive&page=5.html","aktuelles-action%5B8%5D=archive&page=6.html","aktuelles-action%5B8%5D=archive&page=7.html","aktuelles-action%5B8%5D=archive&page=8.html","aktuelles-action%5B8%5D=archive&page=9.html","aktuelles-action%5B8%5D=archive&page=10.html","aktuelles-action%5B8%5D=archive&page=11.html","aktuelles-action%5B8%5D=archive&page=12.html","aktuelles-action%5B8%5D=archive&page=13.html","aktuelles-action%5B8%5D=archive&page=14.html","aktuelles-action%5B8%5D=archive&page=15.html","aktuelles-action%5B8%5D=archive&page=16.html","aktuelles-action%5B8%5D=archive&page=17.html","aktuelles-action%5B8%5D=archive&page=18.html","aktuelles-action%5B8%5D=archive&page=19.html","aktuelles-action%5B8%5D=archive&page=20.html","aktuelles-action%5B8%5D=archive&page=21.html","aktuelles-action%5B8%5D=archive&page=22.html","aktuelles-action%5B8%5D=archive&page=23.html","aktuelles-action%5B8%5D=archive&page=24.html","aktuelles-action%5B8%5D=archive&page=25.html","aktuelles-action%5B8%5D=archive&page=26.html","aktuelles-action%5B8%5D=archive&page=27.html","aktuelles-action%5B8%5D=archive&page=28.html","aktuelles-action%5B8%5D=archive&page=29.html","aktuelles-action%5B8%5D=archive&page=30.html","aktuelles-action%5B8%5D=archive&page=31.html","aktuelles-action%5B8%5D=archive&page=32.html","aktuelles-action%5B8%5D=archive&page=33.html","aktuelles-action%5B8%5D=archive&page=34.html","aktuelles-action%5B8%5D=archive&page=35.html","aktuelles-action%5B8%5D=archive&page=36.html","aktuelles-action%5B8%5D=archive&page=37.html","aktuelles-action%5B8%5D=archive&page=38.html","aktuelles-action%5B8%5D=archive&page=39.html","aktuelles-action%5B8%5D=archive&page=40.html","aktuelles-action%5B8%5D=archive&page=41.html","aktuelles-action%5B8%5D=archive&page=42.html","aktuelles-action%5B8%5D=archive&page=43.html","aktuelles-action%5B8%5D=archive&page=44.html","aktuelles-action%5B8%5D=archive&page=45.html","aktuelles-action%5B8%5D=archive&page=46.html","aktuelles-action%5B8%5D=archive&page=47.html","aktuelles-action%5B8%5D=archive&page=48.html","aktuelles-action%5B8%5D=archive&page=49.html","aktuelles-action%5B8%5D=archive&page=50.html","aktuelles-action%5B8%5D=archive&page=51.html","aktuelles-action%5B8%5D=archive&page=52.html"]';
        $links = json_decode($list, true);
        
        $data = [ ];
        
        foreach ($links as $link) {
            
            $source = file_get_contents('http://www.johannesschule-meppen.de/' . $link);
            
            preg_match_all('#<li><a href="(.*?)">([0-9]{2}\.[0-9]{2}\.[0-9]{4}) (.*?)</a></li>#', $source, $matches);
            
            foreach ($matches as $index => $xxx) {
                
                $data[] = [
                    'date' => (new \Frootbox\Dates\Date($matches[2][$index]))->format('%Y-%m-%d'),
                    'title' => $matches[3][$index],
                    'url' => 'http://www.johannesschule-meppen.de/' . $matches[1][$index]
                ];
            }
        }
        
        
        http_response_code(200);        
        header('Content-disposition: attachment; filename=newsWithUrls.json');
        header('Content-type: application/json');
        die(json_encode($data));
    }
    
    
    
    
    /**
     * 
     */
    public function fggAction (
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files
    ) {
                
        // Turn off output buffering
        ini_set('output_buffering', 'off');
        // Turn off PHP output compression
        ini_set('zlib.output_compression', false);
        		
        //Flush (send) the output buffer and turn off output buffering
        //ob_end_flush();
        while (@ob_end_flush());
        		
        // Implicitly flush the buffer(s)
        ini_set('implicit_flush', true);
        ob_implicit_flush(true);
        
        //prevent apache from buffering it for deflate/gzip
        header('Content-type: text/plain');
        header('Cache-Control: no-cache'); // recommended to prevent caching of event data.
                        
        for($i = 0; $i < 1000; $i++)
        {
        echo ' ';
        }
        		
        flush();
        
        
        /**
         * 
         */        
        $result = $files->fetch([
            'where' => [ 'size' => 0 ],
            'limit' => 200
        ]);
        
        $loop = 0;
        
        foreach ($result as $file) {
            
            // Generate filename
            $name = strtolower($file->getName());
            $x = explode('.', $name);
            $extension = array_pop($x);
            $name = implode('-', $x);
            $name = preg_replace("/[^[:alnum:]]/u", '-', $name);
            $name = str_pad($file->getId(), 7, '0', STR_PAD_LEFT) . '-' . $name . '.' . $extension;
            
            
            $segment = 'uploads/' . substr($file->getDate(), 0, 7) . '/';
            $path = $config->get('filesRootFolder') . $segment;
            
            if (!file_exists($path)) {
                mkdir($path);
            }
            
            $target = $path . $name;
                        
            // Obtain file
            $src = file_get_contents($file->getConfig('url'));
            
            file_put_contents($target, $src);
            
            $file->setSize(filesize($target));
            $file->setPath($segment . $name);
            $file->addConfig([
                'importedFrom' => $file->getConfig('url'),
                'url' => null                
            ]);

            $file->save();
            
            ++$loop;
            
            if ($loop % 10 == 0) {
                echo ($loop / 2) . '%' . PHP_EOL;
                flush();
            }
        }
        
        die("Importiert: " . $loop);
        
        
        exit;
        
        
        
        /**
         * Create empty files
         */
        $json = file_get_contents($this->getPath() . 'galleriesWithIds.json');
        $data = json_decode($json, true);
        
        $folderId = $config->get('statics.files.uploadDefaultFolder');
        $loop = 0;
        
        foreach ($data as $index => $gallery) {
            
            foreach ($gallery['images'] as $url) {
                
                $file = new \Frootbox\Persistence\File([
                    'date' => $gallery['isodate'],
                    'folderId' => $folderId,
                    'name' => basename($url),
                    'type' => 'image/jpeg',
                    'config' => [
                        'url' => $url
                    ],
                    'uid' => 'Frootbox-Ext-Core-Images-Persistence-Repositories-Categories:' . $gallery['categoryId'] . ':images'                                        
                ]);
                
                
                $files->insert($file);
                ++$loop;
            }
        }
        
        http_response_code(200);   
        die("OK imported " . $loop);
        exit;
        
        
        /**
         * 
         */
        $json = file_get_contents($this->getPath() . 'galleriesWithReversed.json');
        $data = json_decode($json, true);
        
        $catCache = [ ];
        
        foreach ($data as $index => $gallery) {
            
            $year = substr($gallery['isodate'], 0, 4);
            
            if (empty($catCache[$year])) {
                
                $result = $categories->fetch([
                    'where' => [ 'title' => $year ],
                    'limit' => 1
                ]);
            
                $catCache[$year] = $result->current();
            }
            
            $parent = $catCache[$year];
            
            $newCat = new \Frootbox\Ext\Core\Images\Persistence\Category([
                'title' => $gallery['title'],
                'date' => $gallery['isodate'],
                'pageId' => $parent->getPageId(),
                'pluginId' => $parent->getPluginId(),
                'uid' => 'Frootbox-Persistence-Repositories-ContentElements:12:categories'
            ]);
                        
            $child = $parent->appendChild($newCat);
            
            $gallery['categoryId'] = $child->getId();
            
            $data[$index] = $gallery;
        }
        
        http_response_code(200);        
        header('Content-disposition: attachment; filename=galleriesWithIds.json');
        header('Content-type: application/json');
        die(json_encode($data));
        
        
        
        
        
        /**
         * 
         */
        $json = file_get_contents($this->getPath() . 'galleriesWithDates.json');
        $data = json_decode($json, true);
        
        $data = array_reverse($data);
        
        http_response_code(200);        
        header('Content-disposition: attachment; filename=galleriesWithReversed.json');
        header('Content-type: application/json');
        die(json_encode($data));
        
        
        /**
         * Rewrite dates
         */
        $tr = [
            ' Januar ' => '01.',
            ' Februar ' => '02.',
            ' März ' => '03.',
            ' April ' => '04.',
            ' Mai ' => '05.',
            ' Juni ' => '06.',
            ' Juli ' => '07.',
            ' August ' => '08.',
            ' September ' => '09.',
            ' Oktober ' => '10.',
            ' November ' => '11.',
            ' Dezember ' => '12.',
            
        ];
        
        $json = file_get_contents($this->getPath() . 'galleriesWithFiles.json');
        $data = json_decode($json, true);
        
        foreach ($data as $index => $gallery) {
            
            $data[$index]['date'] = strtr($gallery['date'], $tr);       
                        
            $date = new \Frootbox\Dates\Date($data[$index]['date']);
            
            $data[$index]['isodate'] = $date->format('%Y-%m-%d');
        }
        
        http_response_code(200);        
        header('Content-disposition: attachment; filename=galleriesWithDates.json');
        header('Content-type: application/json');
        die(json_encode($data));
        
        exit;
        
        /**
         * gather images
         */
        $json = file_get_contents($this->getPath() . 'rawgalleries.json');
        $data = json_decode($json, true);
        
        foreach ($data as $index => $gallery) {
            
            $source = file_get_contents($gallery['url']);
            
            // preg_match_all('#\<a href="(.*?)" (.*?)><img src\=\"(.*?)\" alt=\"\" \/\>\<\/a\>#', $source, $matches);
            preg_match_all('#cms/media/thumbnail_([0-9]{1,})\.(.*?)"#i', $source, $matches);
            
            foreach ($matches[1] as $id) {
                
                $url = 'http://www.johannesschule-meppen.de/cms/media/thumbnail_' . $id . '.jpg';
                
                $data[$index]['images'][] = $url;
            }
            
            
        }
        
        http_response_code(200);        
        header('Content-disposition: attachment; filename=galleriesWithFiles.json');
        header('Content-type: application/json');
        die(json_encode($data));
        
        d($data);
        
        exit;
        
        
        /**
         * Gahter galleries
         */
        $pages = [
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=2.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=3.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=4.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=5.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=6.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=7.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=8.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=9.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=10.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=11.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=12.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=13.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=14.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=15.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=16.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=17.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=18.html',
            'http://www.johannesschule-meppen.de/aktuelles/fotogalerie-page=19.html'
        ];
        
        $list = [ ];
        
        foreach ($pages as $page) {
            
            $source = file_get_contents($page);
            
            preg_match_all('#<h2>(.*?)\<\/h2>.*?vom (.*?)<br />.*?href="(.*?)"#is', $source, $matches);
            
            foreach ($matches[1] as $index => $title) {
                
                $list[] = [
                    'title' => trim($title),
                    'date' => trim($matches[2][$index]),
                    'url' => 'http://www.johannesschule-meppen.de/aktuelles/' . $matches[3][$index],
                    'images' => [ ]
                ];
            }
        }
        
        http_response_code(200);        
        header('Content-disposition: attachment; filename=rawgalleries.json');
        header('Content-type: application/json');
        die(json_encode($list));
        
        
        
        d("OK");
        
        
        d("OK");
    }


    /** 
     * 
     */
    public function indexAction ( ) {

        return self::response();
    }    
}