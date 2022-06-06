<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\News\Admin\Import;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articlesRepository,
        \Frootbox\Persistence\Repositories\Files $filesRepository,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
        \Frootbox\Persistence\Repositories\Folders $foldersRepository
    ): Response
    {

        $result = $articlesRepository->fetch([
            'order' => [ 'dateStart DESC' ]
        ]);

        foreach ($result as $article) {

            if (empty($article->getConfig('file'))) {
                continue;
            }

            d($article);

            if ($article->getConfig('textImported')) {
                continue;
            }



            $url = 'https://www.hairfashion.de/news/' . $article->getConfig('file');
            $source = @file_get_contents($url);

            if (empty($source)) {
                continue;
            }

            $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($source);

            $crawler->filter('.NewsArticles.a1.col-12.col-md-12 .col-md-7.col-sm-12')->each(function ( $element ) use ($article, $textsRepository) {

                $html = trim($element->html());
                $text = $textsRepository->fetchByUid($article->getUid('text'), [ 'createOnMiss' => true ]);

                $text->setText($html);
                $text->save();

                $article->addConfig([
                   'textImported' => true,
                ]);
                $article->save();
            });
        }

        die("NOTHING TO DO");




        // Fetch root folder
        $folder = $foldersRepository->fetchOne([
            'where' => [
                'title' => 'Files Index',
            ],
        ]);

        $result = $articlesRepository->fetch([
            'order' => [ 'dateStart DESC' ]
        ]);


        foreach ($result as $article) {

            if ($article->getConfig('imageImported')) {
                continue;
            }

            $result = $filesRepository->fetch([
                'where' => [
                    'uid' => $article->getUid('image'),
                ],
            ]);

            $result->map('delete');

            $filename = trim($article->getConfig('image'), '#');
            $info = pathinfo($filename);

            $tmpfname = tempnam("/tmp", "FOO");

            if (empty($article->getConfig('image'))) {
                continue;
            }

            $handle = fopen($tmpfname, "w");
            fwrite($handle, file_get_contents($article->getConfig('image')));
            fclose($handle);

            $file = new \Frootbox\Persistence\File([
                'folderId' => $folder->getId(),
                'name' => basename($filename),
                'uid' => $article->getUid('image'),
                'type' => $filesRepository::getMimeTypeFromExtension($info['extension']),
                'sourceFile' => $tmpfname,
            ]);

            // Insert file
            $file = $filesRepository->insert($file);

            $article->addConfig([
                'imageImported' => true,
            ]);
            $article->save();

        }

        die("NOTHING TO DO");





        $result = $articlesRepository->fetch([
            'order' => [ 'dateStart DESC' ]
        ]);

        foreach ($result as $article) {

            $url = 'https://www.hairfashion.de/news/' . $article->getConfig('file');


            $source = @file_get_contents($url);

            if (empty($source)) {

                $article->setVisibility(0);
                $article->save();

                continue;
            }

            $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($source);

            $crawler->filter('.plugin.Images.ImagePic.l1 img')->each(function ( $element ) use ($article) {

                if ($element->getAttribute('src') == '../images/dummy/big.png') {
                    return;
                }

                $imgSrc = 'https://www.hairfashion.de/news/' . $element->getAttribute('src');

                $article->addConfig([
                    'image' => $imgSrc,
                ]);
                $article->save();
            });

        }

        die("NOTHING TO DO");


        $data = '[{"file":"20-09-25-daumen-druecken.html","title":"Daumen dr\u00fccken!"},{"file":"20-09-18-junior-college.html","title":"Junior College Step II"},{"file":"20-09-11-maennersache.html","title":"M\u00c4NNERSACHE!"},{"file":"20-09-03-september-highlight.html","title":"September Highlight"},{"file":"20-08-28-eq.html","title":"EQ \u2013 DEIN EINSTIEG"},{"file":"20-08-21-wellaplex.html","title":"WELLAPLEX"},{"file":"20-08-14-junior-college.html","title":"Junior College Step 7+8"},{"file":"20-08-07-azubistart.html","title":"Azubi-Start 2020"},{"file":"20-07-31-ausbilderprofi.html","title":"Ausbilder-Profi I"},{"file":"20-07-24-highlights.html","title":"Hair Fashion Highlights"},{"file":"20-07-17-inqa-workshop.html","title":"INQA Workshop!"},{"file":"20-07-10-welcome-back.html","title":"Welcome Back!"},{"file":"20-07-03-wella-trainer-college.html","title":"WELLA TRAINER COLLEGE"},{"file":"20-06-26-modelle-gesucht.html","title":"Modelle gesucht!"},{"file":"20-06-19-auf-ein-wort.html","title":"Auf ein Wort ..."},{"file":"20-06-12-daumen-druecken.html","title":"Daumen dr\u00fccken!"},{"file":"20-06-04-virtueller-rundgang.html","title":"Virtueller Rundgang"},{"file":"20-05-29-oeffnungszeiten.html","title":"Neue \u00d6ffnungszeiten!"},{"file":"20-05-25-perspektiven.html","title":"Zukunftsperspektiven!"},{"file":"20-05-15-personalsachbearbeiter-in.html","title":"Personalsachbearbeiter\/-in"},{"file":"20-05-08-azubis-gesucht.html","title":"AZUBIS GESUCHT"},{"file":"20-04-24-corona-informationen.html","title":"Corona Informationen"},{"file":"20-04-10-frohe-ostern.html","title":"Frohe Ostern!"},{"file":"20-03-27-stayathome.html","title":"#stayathome"},{"file":"20-03-13-azubis-gesucht.html","title":"Azubis gesucht!"},{"file":"20-03-06-zehn-jahre-metelen.html","title":"10 Jahre Metelen"},{"file":"20-02-28-feel-good-manager.html","title":"Feel Good Manager"},{"file":"20-02-20-ein-rythmus.html","title":"Ein Rhythmus!"},{"file":"20-02-14-repair-service.html","title":"SP Repair Service"},{"file":"20-02-07-come-together.html","title":"We come together!"},{"file":"20-01-24-jahresauftakt.html","title":"SP Beratungs-Session"},{"file":"20-01-17-pivot-point-schnitt-coach.html","title":"Pivot Point Schnitt Coach"},{"file":"20-01-10-es-ist-geschafft.html","title":"Es ist geschafft!"},{"file":"20-01-03-meister-leistung.html","title":"Meister Leistung!"},{"file":"12-24-frohe-weihnachten.html","title":"Frohe Weihnachten!"},{"file":"12-20-ihr-entscheidet-selbst.html","title":"Ihr entscheidet!"},{"file":"12-13-vote-the-best.html","title":"Vote The Best"},{"file":"12-06-seb-man-xmas.html","title":"SEB MAN X-MAS Aktion"},{"file":"11-29-adventskalender.html","title":"Facebook-Adventskalender"},{"file":"11-22-junior-college-4.html","title":"Junior College Step 4"},{"file":"11-08-trendwoche1.html","title":"Trendwoche 1"},{"file":"11-01-trend-herbst-winter.html","title":"DIMENSIONAL COLORS"},{"file":"10-25-color-session-technik.html","title":"COLOR SESSION TECHNIK"},{"file":"10-18-pivot-point.html","title":"Junior College Step 3"},{"file":"10-04-haar-wars.html","title":"WERDE EIN SCH\u00d6NERMACHER"},{"file":"09-27-master-leistung.html","title":"MASTER-Leistung"},{"file":"09-20-meet-our-trainer-sabrna.html","title":"Meet our Trainer: Sabrina"},{"file":"09-13-erste-hilfe.html","title":"Erstehilfekurs in Lingen"},{"file":"09-06-geschafft.html","title":"GESCHAFFT!"},{"file":"08-30-upgrade-your-style.html","title":"UPGRADE YOUR STYLE!"},{"file":"08-16-meet-our-trainer-gina.html","title":"Meet Our Trainer: Gina"},{"file":"08-09-junior-college-bootcamp.html","title":"Junior College Bootcamp"},{"file":"08-02-azubi-rekordy.html","title":"Azubi-Rekord"},{"file":"07-26-wir-sagen-yay.html","title":"Wir sagen YAY"},{"file":"07-19-meet-our-trainer-dana.html","title":"Meet our trainer: DANA"},{"file":"07-12-pflegesommer.html","title":"Pflegesommer 2019"},{"file":"07-05-bilanzbuchhalter.html","title":"Bilanzbuchhalter\/-in"},{"file":"azubi-bueromanagement.html","title":"Azubi B\u00fcromanagement"},{"file":"junior-college-step-9.html","title":"Junior College Step 9"},{"file":"cut-premiere.html","title":"Cut- Premiere"},{"file":"schoenermacher-geschichte-franzi.html","title":"Sch\u00f6nerMacher Geschichte: Franzi"},{"file":"sl-tag-i.html","title":"SL TAG I"},{"file":"meisterleistung.html","title":"Meisterleistung"},{"file":"seb-man-kick-off.html","title":"SEB MAN Kick- Off"},{"file":"junior-college-step-7-4.html","title":"Junior College Step 7"},{"file":"anne-niemann-geschaeftsfuehrerin.html","title":"Anne Niemann Gesch\u00e4ftsf\u00fchrerin"},{"file":"seb-man.html","title":"SEB MAN"},{"file":"schoenermacher-geschichte-aileen.html","title":"Sch\u00f6nerMacher Geschichte: Aileen"},{"file":"900-gewinnerin-in-appelhuelsen.html","title":"900\u20ac- Gewinnerin in Appelh\u00fclsen"},{"file":"10-trendrabatt.html","title":"10\u20ac Trendrabatt"},{"file":"schoenermacher-isi-und-janet.html","title":"Sch\u00f6nerMacher: Isi & Janet"},{"file":"trendlook-opal-essence.html","title":"TrendLook Opal Essence"},{"file":"der-42-hair-fashion-eroeffnet-in-westerkappeln.html","title":"Der 42. HAIR FASHION er\u00f6ffnet in Westerkappeln"},{"file":"neueroeffnung-westerkappeln-2.html","title":"Neuer\u00f6ffnung Westerkappeln"},{"file":"junior-college-step-5.html","title":"Junior College Step 5"},{"file":"10-jahre-bei-hair-fashion.html","title":"10 Jahre bei HAIR FASHION"},{"file":"mini-nr-4-on-tour.html","title":"MINI Nr. 4 on Tour"},{"file":"andrea-ist-ausbilderin.html","title":"Andrea ist Ausbilderin!"},{"file":"askforwella-gewinnerin.html","title":"#AskforWella Gewinnerin"},{"file":"noch-ganz-frisch.html","title":"Noch ganz frisch?"},{"file":"kevin-ist-springer.html","title":"Kevin ist Springer!"},{"file":"weihnachten-bei-hair-fashion.html","title":"Weihnachten bei HAIR FASHION"},{"file":"guetesiegel-fuer-familienfreundlichkeit.html","title":"G\u00fctesiegel f\u00fcr Familienfreundlichkeit"},{"file":"wir-sind-beklebt.html","title":"Wir sind beklebt!"},{"file":"weihnachten-bei-hair-fashion-4.html","title":"Weihnachten bei HAIR FASHION"},{"file":"wir-ziehen-um.html","title":"Wir ziehen um!"},{"file":"hair-fashion-party-90-3.html","title":"HAIR FASHION Party 9.0"},{"file":"herbst-winter-trend-tage-2018.html","title":"Herbst-\/ Winter Trend-Tage 2018"},{"file":"assistenz-rechnungswesen.html","title":"Assistenz Rechnungswesen"},{"file":"meisterleistungen.html","title":"Meisterleistungen"},{"file":"erste-logoumstellung-erfolgreich.html","title":"Erste Logoumstellung erfolgreich"},{"file":"hair-fashion-party-90.html","title":"HAIR FASHION Party 9.0"},{"file":"junior-college-step-2-geschafft-2.html","title":"Junior College- Step 2 geschafft!"},{"file":"41-hair-fashion-neueroeffnung.html","title":"41. HAIR FASHION Neuer\u00f6ffnung"},{"file":"neueroeffnung-lingen.html","title":"Neuer\u00f6ffnung Lingen"},{"file":"nordhorner-meile.html","title":"Nordhorner Meile"},{"file":"celebrate-color-festival-2018.html","title":"Celebrate Color Festival 2018"},{"file":"junior-college-step-1.html","title":"Junior College Step 1"},{"file":"neue-minis.html","title":"Neue MINIs"},{"file":"azubis-2018.html","title":"Azubis 2018"},{"file":"extraportion-schoenheit.html","title":"Extraportion Sch\u00f6nheit"},{"file":"junior-college-bestanden-4.html","title":"Junior College bestanden"},{"file":"wella-trainer-college.html","title":"WELLA Trainer College"},{"file":"update-neubau.html","title":"Update: Neubau"},{"file":"ferienstart-in-nds.html","title":"Ferienstart in NDS"},{"file":"modelle-gesucht.html","title":"Modelle gesucht"},{"file":"lisa-heyer-jetzt-meisterin.html","title":"Lisa Heyer jetzt Meisterin!"},{"file":"wir-suchen-dich.html","title":"Wir suchen Dich!"},{"file":"teamleiter-on-tour.html","title":"Teamleiter on Tour"},{"file":"hair-fashion-feiert-richtfest.html","title":"HAIR FASHION feiert Richtfest"},{"file":"neueroeffnung-in-nordhorn.html","title":"Neuer\u00f6ffnung in Nordhorn"},{"file":"hair-fashion-verbindet.html","title":"HAIR FASHION verbindet"},{"file":"mitarbeiter-chefsache.html","title":"Mitarbeiter? Chefsache!"},{"file":"wellaplex.html","title":"WELLAPLEX"},{"file":"neu-fusion.html","title":"NEU: Fusion"},{"file":"fruehling-sommer-trend-2018.html","title":"Fr\u00fchling\/ Sommer Trend 2018"},{"file":"junior-college-step-8.html","title":"Junior College Step 8"},{"file":"frohe-ostern.html","title":"Frohe Ostern"},{"file":"endspurt-fuer-unsere-azubis-2018.html","title":"Endspurt f\u00fcr unsere Azubis 2018"},{"file":"top-hair.html","title":"TOP HAIR"},{"file":"salonleiter-tag-in-lingen.html","title":"Salonleiter- Tag in Lingen"},{"file":"trend-collection-tour-2018.html","title":"Trend Collection Tour 2018"},{"file":"top-hair-verlosung.html","title":"TOP HAIR Verlosung"},{"file":"werde-schoenermacher.html","title":"Werde Sch\u00f6nerMacher"},{"file":"junior-college-step-6.html","title":"Junior College Step 6"},{"file":"50-pflege-special.html","title":"50% Pflege- Special!"},{"file":"modell-werden.html","title":"Modell werden"},{"file":"gute-ausbildung-gute-aussichten.html","title":"Gute Ausbildung, gute Aussichten!"},{"file":"spatenstich-trend-studio.html","title":"Spatenstich Trend Studio"},{"file":"ausgezeichnet.html","title":"Ausgezeichnet!"},{"file":"hairliche-weihnachten.html","title":"Hairliche Weihnachten"},{"file":"auf-nach-hamburg.html","title":"Auf nach Hamburg"},{"file":"adventskalender.html","title":"Adventskalender"},{"file":"crazy-christmas-party-80.html","title":"Crazy Christmas Party 8.0"},{"file":"ankuendigung-hf-party-80.html","title":"Ank\u00fcndigung HF Party 8.0"},{"file":"sl-tag-ii.html","title":"SL Tag II"},{"file":"kick-off-neue-tl.html","title":"Kick-Off neue TL"},{"file":"hairfashion-trendsalon-2017.html","title":"Hairfashion Trendsalon 2017"},{"file":"trend-autumn-winter-1718.html","title":"Trend Autumn\/ Winter 17\/18"},{"file":"ich-bin-ein-schoenermacher-weil.html","title":"Ich bin ein Sch\u00f6nerMacher, weil..."},{"file":"jugend-frisiert-azubi-amira-raeumt-ab.html","title":"Jugend frisiert - Azubi Amira r\u00e4umt ab!"},{"file":"salonleitertag-ii.html","title":"Salonleitertag II"},{"file":"wella-trend-vision-award-2017.html","title":"WELLA Trend Vision Award 2017"},{"file":"familenfreundlicher-arbeitgeber.html","title":"Familenfreundlicher Arbeitgeber"},{"file":"betriebsversammlung.html","title":"Betriebsversammlung"},{"file":"bestes-zeugnis.html","title":"Bestes Zeugnis"},{"file":"facebook-verlosung.html","title":"facebook Verlosung"},{"file":"dana-broek-ist-jetzt-pivot-point-cutting-coach.html","title":"Dana Broek ist jetzt Pivot Point Cutting Coach"},{"file":"hair-fashion-goes-facebook.html","title":"Hair Fashion goes Facebook"},{"file":"gesellenpruefungen-bestanden.html","title":"Gesellenpr\u00fcfungen bestanden!"},{"file":"unsere-neuen-auszubildenden-2017.html","title":"Unsere neuen Auszubildenden 2017"},{"file":"erste-hilfe-fuer-schoenermacher.html","title":"Erste Hilfe f\u00fcr Sch\u00f6nerMacher"},{"file":"salonleitungen-werden-experten.html","title":"Salonleitungen werden Experten"},{"file":"schoenermacher-gesucht.html","title":"Sch\u00f6nerMacher gesucht"},{"file":"katrin-lindau-ist-jetzt-meisterin.html","title":"Katrin Lindau ist jetzt Meisterin"},{"file":"jc-pruefung.html","title":"JC Pr\u00fcfung"},{"file":"bestandene-abschlusspruefung.html","title":"Bestandene Abschlusspr\u00fcfung"},{"file":"eimi-entdeckergroessen.html","title":"EIMI Entdeckergr\u00f6\u00dfen"},{"file":"inqa-audit.html","title":"INQA-Audit"},{"file":"jc-step-9.html","title":"JC Step 9"},{"file":"verabschiedung-unserer-schulungsleitung.html","title":"Verabschiedung unserer Schulungsleitung"},{"file":"schoenermacher-fotoshooting.html","title":"Sch\u00f6nerMacher Fotoshooting"},{"file":"mund-auf-staebchen-rein-spender-sein.html","title":"Mund auf, St\u00e4bchen rein, Spender sein!"},{"file":"jc-step-8.html","title":"JC Step 8"},{"file":"inqa-drittes-treffen.html","title":"INQA - Drittes Treffen"},{"file":"inqa-audit-zweites-treffen.html","title":"INQA-Audit - Zweites Treffen"},{"file":"hair-fashion-wuenscht-schoene-ostern.html","title":"HAIR FASHION W\u00dcNSCHT SCH\u00d6NE OSTERN"},{"file":"top-hair-in-duesseldorf.html","title":"TOP HAIR in D\u00fcsseldorf"},{"file":"top-farbtrend-schulung-fuer-schoenermacher.html","title":"Top-Farbtrend-Schulung f\u00fcr Sch\u00f6nerMacher"},{"file":"lust-auf-veraenderung.html","title":"LUST AUF VER\u00c4NDERUNG"},{"file":"tigi-produkt-schulung.html","title":"TIGI-PRODUKT-SCHULUNG"},{"file":"junior-college-step-7.html","title":"Junior College Step 7"},{"file":"top-hair-verlosung-3.html","title":"Top-Hair Verlosung"},{"file":"junior-college-step-6-2.html","title":"Junior College Step 6"},{"file":"katharina-isermann-ist-jetzt-meisterin.html","title":"Katharina Isermann ist jetzt Meisterin"},{"file":"inqa-audit-erstes-treffen.html","title":"INQA-Audit - Erstes Treffen"},{"file":"inqa-audit-2.html","title":"INQA-Audit"},{"file":"ausbildungsplatzboerse-in-aschendorf.html","title":"Ausbildungsplatzb\u00f6rse in Aschendorf"},{"file":"luxus-service-bei-hair-fashion.html","title":"Luxus-Service bei Hair Fashion"},{"file":"hair-fashion-startet-ausbildungsoffensive.html","title":"Hair Fashion startet Ausbildungsoffensive"},{"file":"jc-step-5-system-professional.html","title":"JC Step 5: System Professional"},{"file":"hair-fashion-wuenscht-schoene-weihnachten.html","title":"HAIR FASHION W\u00dcNSCHT SCH\u00d6NE WEIHNACHTEN"},{"file":"luxeoil-collection.html","title":"LuxeOil Collection"},{"file":"master-of.html","title":"Master of"},{"file":"sl-tag.html","title":"SL Tag"},{"file":"jc-step-4.html","title":"JC Step 4"},{"file":"ausbildungsplatzboerse-pap.html","title":"Ausbildungsplatzb\u00f6rse PAP"},{"file":"itva-in-barcelona.html","title":"ITVA in Barcelona"},{"file":"tt-wella-herbst-winter.html","title":"T+T Wella Herbst-Winter"},{"file":"jc-step-3.html","title":"JC Step 3"},{"file":"trend-vision-award-2016.html","title":"TREND VISION AWARD 2016"},{"file":"ausbildungsplatzboerse-hop.html","title":"Ausbildungsplatzb\u00f6rse HOP"},{"file":"jc-step-2.html","title":"JC Step 2"},{"file":"hair-fashion-goes-to-mallorca.html","title":"Hair Fashion goes to Mallorca"},{"file":"jc-step-1.html","title":"JC Step 1"},{"file":"neueroeffnung-in-papenburg.html","title":"Neuer\u00f6ffnung in Papenburg"},{"file":"hair-fashion-party-70.html","title":"Hair Fashion Party 7.0"},{"file":"ankuendigung-papenburg.html","title":"Ank\u00fcndigung Papenburg"},{"file":"neue-oeffnungszeiten.html","title":"Neue \u00d6ffnungszeiten"},{"file":"mallorca-party.html","title":"Mallorca Party"},{"file":"gesellenpruefung-bestanden.html","title":"Gesellenpr\u00fcfung bestanden"},{"file":"unsere-auszubildenden-2016.html","title":"Unsere Auszubildenden 2016"},{"file":"mit-wella-nach-mallorca.html","title":"Mit Wella nach Mallorca"},{"file":"anne-niemann-zur-prokuristin-berufen.html","title":"Anne Niemann zur Prokuristin berufen"},{"file":"teamleiter-mw-personal.html","title":"Teamleiter (M\/W) Personal"},{"file":"hair-fashion-kicktipp-2016.html","title":"HAIR FASHION KICKTIPP 2016"},{"file":"teamleitung-erhaelt-verstaerkung.html","title":"Teamleitung erh\u00e4lt Verst\u00e4rkung"},{"file":"junior-college-bestanden.html","title":"JUNIOR COLLEGE BESTANDEN!"},{"file":"neueroeffnung-ems.html","title":"Neuer\u00f6ffnung EMS"},{"file":"lust-auf-veraenderung-2.html","title":"Lust auf Ver\u00e4nderung?"},{"file":"quiz-champion.html","title":"Quiz-Champion"},{"file":"kristina-arngold-ist-jetzt-meisterin.html","title":"Kristina Arngold ist jetzt Meisterin"},{"file":"jc-step-9-pruefungsvorbereitung.html","title":"JC Step 9: Pr\u00fcfungsvorbereitung"},{"file":"gute-leistungen-lohnen-sich.html","title":"Gute Leistungen lohnen sich"},{"file":"frohe-pfingsten.html","title":"Frohe Pfingsten"},{"file":"zurueck-aus-muenchen.html","title":"Zur\u00fcck aus M\u00fcnchen"},{"file":"neueroeffnung-esterwegen.html","title":"Neuer\u00f6ffnung Esterwegen"},{"file":"junior-college-step-8-2.html","title":"Junior College Step 8"},{"file":"junior-college-step-7-2.html","title":"Junior College Step 7"},{"file":"vorankuendigung-eroeffnung-esterwegen.html","title":"Vorank\u00fcndigung Er\u00f6ffnung Esterwegen"},{"file":"top-hair-2016.html","title":"Top Hair 2016"},{"file":"frohe-ostern-2.html","title":"Frohe Ostern"},{"file":"top-farbtrend-schulung-fuer-schoenermacher-2.html","title":"Top-Farbtrend-Schulung f\u00fcr Sch\u00f6nerMacher"},{"file":"du-bist-ein-macher-werde-schoenermacher.html","title":"DU BIST EIN MACHER? - WERDE SCH\u00d6NERMACHER!"},{"file":"permanente-umformung-und-hochstecken.html","title":"Permanente Umformung und Hochstecken"},{"file":"grossen-preis-des-mittelstandes-2016.html","title":"Gro\u00dfen Preis des Mittelstandes 2016"},{"file":"ausbildungsplatzboerse-aschendorf.html","title":"Ausbildungsplatzb\u00f6rse Aschendorf"},{"file":"top-hair-verlosung-4.html","title":"Top Hair Verlosung"},{"file":"talentetag-bei-hair-fashion.html","title":"Talentetag bei HAIR FASHION"},{"file":"die-zukunft-schoener-machen.html","title":"Die Zukunft sch\u00f6ner machen"},{"file":"hair-fashion-startet-ausbildungs-offensive.html","title":"Hair Fashion startet Ausbildungs-Offensive"},{"file":"jede-menge-meisterleistungen.html","title":"JEDE MENGE MEISTERLEISTUNGEN"},{"file":"frohe-weihnachten.html","title":"FROHE WEIHNACHTEN"},{"file":"tigi-verlosung.html","title":"TIGI-Verlosung"},{"file":"kostenloses-wlan.html","title":"Kostenloses WLAN"},{"file":"nikolaus.html","title":"Nikolaus"},{"file":"trendvision-award.html","title":"TrendVision Award"},{"file":"ausbildungsplatzboerse-twist.html","title":"Ausbildungsplatzb\u00f6rse Twist"},{"file":"moonlight-shopping.html","title":"Moonlight-Shopping"},{"file":"feuer-und-flamme-fuer-damme.html","title":"Feuer & Flamme f\u00fcr Damme"},{"file":"soegeler-schoenermacher-helfen-fluechtlingen.html","title":"S\u00f6geler Sch\u00f6nerMacher helfen Fl\u00fcchtlingen"},{"file":"neueroeffnung-in-schuettorf.html","title":"Neuer\u00f6ffnung in Sch\u00fcttorf"},{"file":"trend-vision-award-in-duesseldorf.html","title":"Trend Vision Award in D\u00fcsseldorf"},{"file":"wir-begruessen-kleine-schoenermacher.html","title":"Wir begr\u00fcssen kleine Sch\u00f6nermacher."},{"file":"neueroeffnung-in-altenberge.html","title":"Neuer\u00f6ffnung in Altenberge"},{"file":"neue-springerfahrzeuge.html","title":"Neue Springerfahrzeuge"},{"file":"oktoberfest-in-lingen.html","title":"Oktoberfest in Lingen"},{"file":"jede-menge-meister-leistungen.html","title":"Jede Menge Meister-Leistungen"},{"file":"unsere-auszubildenden-2015.html","title":"Unsere Auszubildenden 2015"},{"file":"gesellenpruefung-2015-bestanden.html","title":"Gesellenpr\u00fcfung 2015 bestanden!"},{"file":"neueroeffnung-in-velen.html","title":"Neuer\u00f6ffnung in Velen"},{"file":"neueroeffnung-ankuendigung.html","title":"Neuer\u00f6ffnung - Ank\u00fcndigung"},{"file":"ausbildung-bei-den-schoenermachern.html","title":"Ausbildung bei den Sch\u00f6nerMachern"},{"file":"junior-college-2015-bestanden.html","title":"Junior College 2015 bestanden!"},{"file":"assistent-der-geschaeftsfuehrung.html","title":"Assistent der Gesch\u00e4ftsf\u00fchrung"},{"file":"ausbildungsplatzboerse-in-lathen.html","title":"Ausbildungsplatzb\u00f6rse in Lathen"},{"file":"bei-den-schoenermachern-fallen-die-meister-vom-himmel.html","title":"Bei den Sch\u00f6nerMachern fallen die Meister vom Himmel"},{"file":"austausch-abenteuer-lesund.html","title":"Austausch-Abenteuer \u00c5lesund"},{"file":"schoenermacher-auch-ersthelfer.html","title":"Sch\u00f6nerMacher auch ErstHelfer"},{"file":"neue-produkte-testen-eimi-styling-abend.html","title":"Neue Produkte testen - EIMI Styling Abend"},{"file":"hair-fashion-wieder-nominiert.html","title":"HAIR FASHION wieder nominiert"},{"file":"austausch-azubi-anndrea-zu-gast-bei-hair-fashion.html","title":"Austausch-Azubi Anndrea zu Gast bei Hair Fashion"},{"file":"ostern.html","title":"Ostern"},{"file":"neueroeffnung-in-aschendorf.html","title":"Neuer\u00f6ffnung in Aschendorf"},{"file":"neueroeffnung-in-aschendorf-ankuendigung.html","title":"Neuer\u00f6ffnung in Aschendorf - Ank\u00fcndigung"},{"file":"wie-schoenermacher-die-zukunft-schoener-machen.html","title":"Wie Sch\u00f6nerMacher die Zukunft sch\u00f6ner machen"},{"file":"wir-schoenermacher-wachsen-und-suchen-dich.html","title":"Wir Sch\u00f6nerMacher wachsen und suchen Dich!"},{"file":"schoenermacher-timo-wickert-besteht-pruefung-und-begeistert-kunden.html","title":"Sch\u00f6nerMacher Timo Wickert besteht Pr\u00fcfung und begeistert Kunden"},{"file":"hair-fashion-ermoeglicht-azubi-austausch-mit-norwegen.html","title":"HAIR FASHION erm\u00f6glicht Azubi-Austausch mit Norwegen"},{"file":"erfolgreich-schlauermachen-fuer-schoenermacher.html","title":"Erfolgreich SchlauerMachen f\u00fcr Sch\u00f6nerMacher"},{"file":"guetesiegel-beruf-und-familie.html","title":"G\u00fctesiegel Beruf und Familie"},{"file":"erfolgreiche-eroeffnung-in-damme.html","title":"Erfolgreiche Er\u00f6ffnung in Damme"},{"file":"wella-instamatic-so-einfach-wie-instagram.html","title":"Wella INSTAMATIC - so einfach wie Instagram"},{"file":"weihnachten-2014.html","title":"Weihnachten 2014"},{"file":"neueroeffnung-in-damme.html","title":"Neuer\u00f6ffnung in Damme"},{"file":"viel-haarmonie-zum-nikolausfest.html","title":"Viel Haarmonie zum Nikolausfest!"},{"file":"babyliss-pro-miracurl.html","title":"BaByliss PRO MiraCurl"},{"file":"janina-voet-hat-die-meisterpruefung-bestanden.html","title":"Janina Voet hat die Meisterpr\u00fcfung bestanden"},{"file":"neueroeffnung-in-damme-ankuendigung.html","title":"Neuer\u00f6ffnung in Damme Ank\u00fcndigung"},{"file":"alte-zoepfe-abschneiden.html","title":"Alte Z\u00f6pfe abschneiden?"},{"file":"schoenermacher-im-schulzentrum-lohne.html","title":"Sch\u00f6nerMacher im Schulzentrum Lohne"},{"file":"hochzeitsgesellschaft-in-fuerstenau.html","title":"Hochzeitsgesellschaft in F\u00fcrstenau"},{"file":"marissa-jarck-ist-jetzt-meisterin.html","title":"Marissa Jarck ist jetzt Meisterin"},{"file":"tigi-world-release-tour-2014.html","title":"TIGI World Release Tour 2014"},{"file":"es-wird-schoener-in-berlin.html","title":"Es wird sch\u00f6ner in Berlin!"},{"file":"junior-college-step-2-geschafft.html","title":"Junior College: Step 2 geschafft!"},{"file":"the-invisibobble-haarstyling-mit-koepfchen.html","title":"The Invisibobble - Haarstyling mit K\u00f6pfchen!"},{"file":"meisterpruefung-erfolgreich-absolviert.html","title":"Meisterpr\u00fcfung erfolgreich absolviert"},{"file":"erfolgreiches-tippspiel-bei-hair-fashion.html","title":"Erfolgreiches Tippspiel bei Hair Fashion"},{"file":"unsere-azubis-2014.html","title":"Unsere Azubis 2014"},{"file":"gesellenpruefung-bestanden-2.html","title":"Gesellenpr\u00fcfung bestanden!"},{"file":"wm-tippspiel-in-recke.html","title":"WM-Tippspiel in Recke"},{"file":"ausbildung-beendet.html","title":"Ausbildung beendet!?"},{"file":"neueroeffnung-in-roxel.html","title":"Neuer\u00f6ffnung in Roxel"},{"file":"hollywood-dance-fashion-50.html","title":"Hollywood Dance Fashion 5.0"},{"file":"abschlusspruefung-erfolgreich-absolviert.html","title":"Abschlusspr\u00fcfung erfolgreich absolviert!"},{"file":"meisterin-und-managerin.html","title":"Meisterin und Managerin"},{"file":"junior-college-bestanden-2.html","title":"Junior College bestanden!"},{"file":"ist-fachlich-fit-macht-fachlich-fit.html","title":"Ist fachlich fit, macht fachlich fit"},{"file":"master-of-hair-fashion.html","title":"Master of Hair Fashion"},{"file":"in-weniger-als-vier-jahren-zur-salonleiterin.html","title":"In weniger als vier Jahren zur Salonleiterin"},{"file":"neueroeffnung-in-ankum.html","title":"Neuer\u00f6ffnung in Ankum"},{"file":"neueroeffnung-in-ankum-ankuendigung.html","title":"Neuer\u00f6ffnung in Ankum - Ank\u00fcndigung"},{"file":"hair-fashion-nominiert.html","title":"HAIR FASHION nominiert"},{"file":"hollywood-50.html","title":"Hollywood 5.0"},{"file":"schoenermacher-lernen-nie-aus-master-of-color-by-hair-fashion.html","title":"SCH\u00d6NERMACHER LERNEN NIE AUS MASTER OF COLOR BY HAIR FASHION"},{"file":"gymnasium-georgianum-erhaelt-mobiles-smartboard.html","title":"Gymnasium Georgianum erh\u00e4lt mobiles Smartboard"},{"file":"hair-fashion-schlager-move-party-2013.html","title":"HAIR FASHION - Schlager Move Party 2013"},{"file":"neueroeffnung-in-neuenkirchen.html","title":"Neuer\u00f6ffnung in Neuenkirchen"},{"file":"neueroeffnung-neuenkirchen.html","title":"Neuer\u00f6ffnung Neuenkirchen"},{"file":"unsere-azubis-2013.html","title":"Unsere Azubis 2013"},{"file":"hair-fashion-junior-college-20122013.html","title":"HAIR FASHION - Junior College  2012\/2013"},{"file":"wella-professionals-trend-visionen-2013.html","title":"Wella Professionals Trend Visionen 2013"},{"file":"wir-suchen.html","title":"Wir suchen!"},{"file":"neueroeffnung-in-holdorf.html","title":"Neuer\u00f6ffnung in Holdorf"},{"file":"betriebliche-altersversorgung-bei-hair-fashion.html","title":"Betriebliche Altersversorgung bei HAIR FASHION"},{"file":"hair-fashion-vip-party-2012.html","title":"HAIR FASHION VIP-Party 2012"},{"file":"unsere-azubis-2012.html","title":"Unsere Azubis 2012"},{"file":"neueroeffnung-in-holdorf-vorankuendigung.html","title":"Neuer\u00f6ffnung in Holdorf - Vorank\u00fcndigung"},{"file":"am-puls-der-zeit.html","title":"Am Puls der Zeit:"},{"file":"neueroeffnung-in-haseluenne.html","title":"Neuer\u00f6ffnung in Hasel\u00fcnne"},{"file":"hair-fashion-im-kindergarten.html","title":"HAIR FASHION im Kindergarten"},{"file":"muttertagsgeschenk-bei-hair-fashion.html","title":"Muttertagsgeschenk bei HAIR FASHION"},{"file":"fruehlingsmarkt-twist.html","title":"Fr\u00fchlingsmarkt Twist"},{"file":"neueroeffnung-hair-fashion-friseur-shop-hille-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung HAIR FASHION Friseur + Shop Hille - Kommen Sie zu uns!"},{"file":"lehrstellenboerse-mit-ffn.html","title":"Lehrstellenb\u00f6rse mit ffn"},{"file":"3-karnevalsschminken-im-hair-fashion-friseur-shop-lastrup.html","title":"3. Karnevalsschminken im HAIR FASHION Friseur + Shop Lastrup"},{"file":"junges-expertenteam-besucht-hair-fashion.html","title":"Junges \"Expertenteam\" besucht HAIR FASHION"},{"file":"seminar-erfolg-ist-planbar.html","title":"Seminar \"ERFOLG IST PLANBAR\""},{"file":"pivot-point-schneideausbildung.html","title":"Pivot Point - Schneideausbildung"},{"file":"nikolausbesuch-in-unseren-salons.html","title":"Nikolausbesuch in unseren Salons"},{"file":"wella-system-professional-sp-berlin.html","title":"WELLA System Professional \"SP\" Berlin"},{"file":"hair-tattoo-seminar.html","title":"Hair Tattoo Seminar"},{"file":"verkaufsoffener-sonntag-im-hair-fashion-salon-barnstorf.html","title":"Verkaufsoffener Sonntag im HAIR FASHION Salon Barnstorf"},{"file":"hair-fashion-macht-mit-bei-tanzrausch-statt-vollrausch.html","title":"HAIR FASHION macht mit bei Tanzrausch statt Vollrausch"},{"file":"hair-fashion-salon-rahden-oeffnet-zum-rahdener-troedelmarkt.html","title":"HAIR FASHION Salon Rahden - \u00f6ffnet zum Rahdener Tr\u00f6delmarkt"},{"file":"ausbildung-bei-hair-fashion-mit-dem-junior-college.html","title":"Ausbildung bei HAIR FASHION mit dem Junior College"},{"file":"neueroeffnung-hair-fashion-friseur-shop-twist-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung HAIR FASHION Friseur + Shop Twist - Kommen Sie zu uns!"},{"file":"neueroeffnung-hair-fashion-friseur-shop-werlte-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung HAIR FASHION Friseur + Shop Werlte - Kommen Sie zu uns!"},{"file":"unsere-azubis.html","title":"Unsere Azubis"},{"file":"hair-fashion-night.html","title":"HAIR FASHION NIGHT"},{"file":"neueroeffnung-hair-fashion-friseur-shop-barnstorf-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung HAIR FASHION Friseur + Shop Barnstorf - Kommen Sie zu uns!"},{"file":"hair-fashion-mit-goldwell-nach-kapstadt.html","title":"HAIR FASHION mit Goldwell nach Kapstadt"},{"file":"neueroeffnung-hair-fashion-friseur-shop-wietmarschen-lohne-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung HAIR FASHION Friseur + Shop Wietmarschen-Lohne - Kommen Sie zu uns!"},{"file":"hair-fashion-schult-das-neue-wella-styling.html","title":"HAIR FASHION schult das neue Wella Styling"},{"file":"neueroeffnung-hair-fashion-friseur-shop-boesel-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung HAIR FASHION Friseur + Shop B\u00f6sel - Kommen Sie zu uns!"},{"file":"hair-fashion-team-soegel-engagiert-sich-fuer-die-dkms.html","title":"HAIR FASHION Team S\u00f6gel engagiert sich f\u00fcr die DKMS"},{"file":"2-karnevalsschminken-im-hair-fashion-friseur-shop-lastrup.html","title":"2. Karnevalsschminken im HAIR FASHION Friseur + Shop Lastrup"},{"file":"neueroeffnung-hair-fashion-friseur-shop-rahden-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung HAIR FASHION Friseur + Shop Rahden - Kommen Sie zu uns!"},{"file":"neueroeffnung-hair-fashion-friseur-shop-hopsten-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung HAIR FASHION Friseur + Shop Hopsten - Kommen Sie zu uns!"},{"file":"trend-tage-in-berlin.html","title":"Trend-Tage in Berlin"},{"file":"hair-worktrend-vision-award.html","title":"hair @ work\/Trend Vision Award!"},{"file":"hair-fashion-after-work-styling-party.html","title":"Hair Fashion After-Work Styling Party"},{"file":"neueroeffnung-hair-fashion-friseur-shop-ochtrup-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung Hair Fashion Friseur +  Shop Ochtrup - Kommen Sie zu uns!"},{"file":"hair-fashion-tigi-aktion-jetzt-bei-uns-im-dalumer-salon.html","title":"Hair Fashion-TIGI Aktion jetzt bei uns im Dalumer Salon!"},{"file":"california-color-feeling-bei-frederic-fekkai-in-los-angeles.html","title":"California Color Feeling bei Frederic Fekkai in Los Angeles"},{"file":"neueroeffnung-hair-fashion-friseur-shop-hilter-kommen-sie-zu-uns.html","title":"Neuer\u00f6ffnung Hair Fashion Friseur Shop Hilter - Kommen Sie zu uns!"},{"file":"neuer-hair-fashion-friseur-shop-westerstede-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Friseur   Shop Westerstede - Kommen Sie zu uns!"},{"file":"westfalischer-hansetag-in-fuerstenau.html","title":"WESTFALISCHER HANSETAG in F\u00fcrstenau"},{"file":"farbspecial-bei-hair-fashion.html","title":"Farbspecial bei Hair Fashion"},{"file":"neuer-hair-fashion-friseur-shop-havixbeck-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Friseur + Shop Havixbeck - Kommen Sie zu uns!"},{"file":"neuer-hair-fashion-friseur-shop-bad-iburg-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Friseur + Shop Bad Iburg - Kommen Sie zu uns!"},{"file":"neuer-hair-fashion-friseur-shop-dalum-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Friseur + Shop Dalum - Kommen Sie zu uns!"},{"file":"hair-fashion-by-hairspray.html","title":"Hair Fashion by Hairspray"},{"file":"neuer-hair-fashion-friseur-shop-steinfeld-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Friseur + Shop Steinfeld - Kommen Sie zu uns!"},{"file":"auszubildenen-casting-bei-hair-fashion.html","title":"Auszubildenen Casting bei Hair Fashion"},{"file":"hairspray-jetzt-gehts-bald-los.html","title":"Hairspray - Jetzt gehts bald los!"},{"file":"neuer-hair-fashion-salon-in-bevergern-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Salon in Bevergern - Kommen Sie zu uns!"},{"file":"neuer-hair-fashion-salon-in-rhauderfehn-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Salon in Rhauderfehn - Kommen Sie zu uns!"},{"file":"hair-fashion-mit-dabei-bei-alice-im-emsland.html","title":"Hair Fashion mit dabei bei \"Alice im Emsland\""},{"file":"fruehlingsfest-im-hair-fashion-salon-doerpen.html","title":"Fr\u00fchlingsfest im Hair Fashion Salon D\u00f6rpen"},{"file":"neuer-hair-fashion-salon-in-emstek-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Salon in Emstek - Kommen Sie zu uns!"},{"file":"neuer-hair-fashion-salon-in-metelen-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Salon in Metelen - Kommen Sie zu uns!"},{"file":"karnevalsschminken-im-salon-lastrup.html","title":"Karnevalsschminken im Salon Lastrup"},{"file":"neuer-hair-fashion-salon-in-laer-kommen-sie-zu-uns.html","title":"Neuer Hair Fashion Salon in Laer - Kommen Sie zu uns!"},{"file":"hairspray-weiterbildung-der-besonderen-art.html","title":"Hairspray - Weiterbildung der besonderen Art"},{"file":"tigi-zu-gast-bei-hair-fashion.html","title":"Tigi zu Gast bei Hair Fashion"},{"file":"hairfashion-friseurde-online.html","title":"hairfashion-friseur.de online"},{"file":"frisurenschau-auf-schloss-clemenswerthsoegel.html","title":"Frisurenschau auf Schloss Clemenswerth\/S\u00f6gel"},{"file":"international-trend-vision-award-2009.html","title":"International Trend Vision Award 2009"},{"file":"sp-neueinfuehrung-im-hotel-am-wasserfall.html","title":"SP Neueinf\u00fchrung im Hotel \"Am Wasserfall\""}]';
        $articles = json_decode($data, true);

        $lastdate = null;
        $loop = 0;

        foreach($articles as $index => $article) {

            $article['pageId'] = $this->plugin->getPageId();
            $article['pluginId'] = $this->plugin->getId();
            $article['config']['file'] = $article['file'];

            if (preg_match('#^([0-9]{2}\-[0-9]{2}\-[0-9]{2})-#', $article['file'], $match)) {
                $article['dateStart'] = $match[1];
                $lastdate = new \DateTime($match[1]);
            }
            else {
                $lastdate->modify('-1 week');

                $article['dateStart'] = $lastdate->format('Y-m-d');
                $article['config']['autodate'] = true;
            }

            unset($article['file']);

            $article = $articlesRepository->insert(new \Frootbox\Ext\Core\News\Persistence\Article($article));

            ++$loop;
        }

        d("OK: " . $loop);
        d($articles);


        $articles = [];

        for ($i = 1; $i <= 17; ++$i) {

            if ($i == 1) {
                $url = 'https://www.hairfashion.de/news-action%5B60%5D=archive.html';
            }
            else {
                $url = 'https://www.hairfashion.de/news-action%5B60%5D=archive&page=' . $i . '.html';
            }


            $source = file_get_contents($url);

            preg_match_all('#<li><a href="news\/(.*?)">(.*?)</a></li>#is', $source, $matches);

            foreach ($matches[1] as $index => $file) {

                $articles[] = [
                    'file' => $file,
                    'title' => $matches[2][$index],
                ];
            }
        }

        d(json_encode($articles));

        exit;

        if (file_exists(__DIR__ . '/data.json')) {
            $pages = file_get_contents(__DIR__ . '/data.json');
            $pages = json_Decode($pages, true);
        }
        else {
            $pages = [];
        }


        for ($loop = 1; $loop <= 33; ++$loop) {

            if (isset($pages[$loop])) {
                continue;
            }

            $pages[$loop] = [
                'page' => $loop,
                'articles' => [],
            ];


            if ($loop == 1) {
                $url = 'https://web.archive.org/web/20191123072512/http://www.staedtering.net/de/aktuelles.html?action[8]=archives';
            }
            else {
                $url = 'https://web.archive.org/web/20191123072512/http://www.staedtering.net/de/aktuelles.html?action[8]=archives&page=' . $loop;
            }

            $source = file_get_contents($url);

            $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($source);

            $crawler->filter('.news-article-teaser-3 a')->each(function ( $element ) use ($articles, &$pages, $loop) {

                $title = preg_replace('#<span class="date">(.*?)</span>#', '', $element->html());

                $pages[$loop]['articles'][] = [
                    'title' => trim($title),
                    'href' => $element->getAttribute('href'),
                ];

                if ($loop == 2) {
                    d($pages);
                }

            });

            file_put_contents(__DIR__ . '/data.json', json_encode($pages));
        }


        d($pages);

        return self::getResponse();
    }
}
