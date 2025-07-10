<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Import;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
    ): Response
    {



        return self::getResponse();
        /*
        $result = $productsRepository->fetch();

        foreach ($result as $product) {

            if (empty($xdata = $product->getConfig('xdata'))) {
                continue;
            }

            foreach ($xdata['images']['gallery'] as $index => $imgurl) {

                $imgurl = 'https://moebel-luebbering.de' . $imgurl;

                $source = file_get_contents($imgurl);
                $tmpfname = tempnam(sys_get_temp_dir(), md5(microtime(true)));
                $info = pathinfo($imgurl);

                $type = $files::getMimeTypeFromExtension($info['extension']);

                $handle = fopen($tmpfname, "w");
                fwrite($handle, $source);

                $uid = $index == 0 ? $product->getUid('image') : $product->getUid('images');

                // Insert file
                $file = $files->insert(new \Frootbox\Persistence\File([
                    'folderId' => $config->get('statics.files.uploadDefaultFolder'),
                    'name' => basename($imgurl),
                    'uid' => $uid,
                    'type' => $type,
                    'size' => filesize($tmpfname),
                    'sourceFile' => $tmpfname,
                    'targetPath' => $config->get('filesRootFolder')
                ]));

                fclose($handle);
            }

            foreach ($xdata['images']['floorplan'] as $imgurl) {

                $imgurl = 'https://moebel-luebbering.de' . $imgurl;


                $source = file_get_contents($imgurl);
                $tmpfname = tempnam(sys_get_temp_dir(), md5(microtime(true)));
                $info = pathinfo($imgurl);

                $type = $files::getMimeTypeFromExtension($info['extension']);

                $handle = fopen($tmpfname, "w");
                fwrite($handle, $source);

                // Insert file
                $file = $files->insert(new \Frootbox\Persistence\File([
                    'folderId' => $config->get('statics.files.uploadDefaultFolder'),
                    'name' => basename($imgurl),
                    'uid' => $product->getUid('floorplan'),
                    'type' => $type,
                    'size' => filesize($tmpfname),
                    'sourceFile' => $tmpfname,
                    'targetPath' => $config->get('filesRootFolder')
                ]));

                fclose($handle);
            }

            foreach ($xdata['images']['views'] as $imgurl) {

                $imgurl = 'https://moebel-luebbering.de' . $imgurl;


                $source = file_get_contents($imgurl);
                $tmpfname = tempnam(sys_get_temp_dir(), md5(microtime(true)));
                $info = pathinfo($imgurl);

                $type = $files::getMimeTypeFromExtension($info['extension']);

                $handle = fopen($tmpfname, "w");
                fwrite($handle, $source);

                // Insert file
                $file = $files->insert(new \Frootbox\Persistence\File([
                    'folderId' => $config->get('statics.files.uploadDefaultFolder'),
                    'name' => basename($imgurl),
                    'uid' => $product->getUid('views'),
                    'type' => $type,
                    'size' => filesize($tmpfname),
                    'sourceFile' => $tmpfname,
                    'targetPath' => $config->get('filesRootFolder')
                ]));

                fclose($handle);
            }

            foreach ($xdata['images']['energy'] as $imgurl) {

                $imgurl = 'https://moebel-luebbering.de' . $imgurl;


                $source = file_get_contents($imgurl);
                $tmpfname = tempnam(sys_get_temp_dir(), md5(microtime(true)));
                $info = pathinfo($imgurl);

                $type = $files::getMimeTypeFromExtension($info['extension']);

                $handle = fopen($tmpfname, "w");
                fwrite($handle, $source);

                // Insert file
                $file = $files->insert(new \Frootbox\Persistence\File([
                    'folderId' => $config->get('statics.files.uploadDefaultFolder'),
                    'name' => basename($imgurl),
                    'uid' => $product->getUid('energy'),
                    'type' => $type,
                    'size' => filesize($tmpfname),
                    'sourceFile' => $tmpfname,
                    'targetPath' => $config->get('filesRootFolder')
                ]));

                fclose($handle);
            }


            foreach ($xdata['images']['3d'] as $imgurl) {

                $imgurl = 'https://moebel-luebbering.de' . $imgurl;


                $source = file_get_contents($imgurl);
                $tmpfname = tempnam(sys_get_temp_dir(), md5(microtime(true)));
                $info = pathinfo($imgurl);

                $type = $files::getMimeTypeFromExtension($info['extension']);

                $handle = fopen($tmpfname, "w");
                fwrite($handle, $source);

                // Insert file
                $file = $files->insert(new \Frootbox\Persistence\File([
                    'folderId' => $config->get('statics.files.uploadDefaultFolder'),
                    'name' => basename($imgurl),
                    'uid' => $product->getUid('3d'),
                    'type' => $type,
                    'size' => filesize($tmpfname),
                    'sourceFile' => $tmpfname,
                    'targetPath' => $config->get('filesRootFolder')
                ]));

                fclose($handle);
            }


        }

        die("Set images");
        exit;


        $json = <<<'EOD'
    [{"title":"Koje 15","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje15","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8166.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8178.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8181.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8168.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8183.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8174.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8171.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8173.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8175.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8180.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8169.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8170.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8167.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/IMG-8179.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/grundriss2.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/ansicht-2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/ansicht-3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/k15.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-15\/perspektive2.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":"Einbauküche von Burger, Landhausfront, ca. 250 x 320 cm, Inklusive Granitarbeitsplatte & Elektrogeräte von Siemens und Liebherr."},"manufacturer":"Burger K\u00fcchen"},{"title":"Koje 18","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje18","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8220.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8221.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8227.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8229.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8224.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8228.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8225.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8223.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8222.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8230.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8231.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-8232.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/grundriss2.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/Ansicht-2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/Ansicht-3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/k18.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/Perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 22","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje22","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6105.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6106.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6107.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6108.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6109.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6110.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6111.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6112.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6113.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6114.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6115.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/IMG-6116.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/grundriss4.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Ansicht-4.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/koje22.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 29","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje29","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7942.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7943.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7945.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/DSC0193.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/DSC0195.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7952.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7946.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7950.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7953.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7954.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7956.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7957.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/grundriss2.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/ansicht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/ansicht3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/Koje-29.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/perspektive2.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 31","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje31","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0172.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0124.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0126.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0131.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0133.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0137.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0140.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0143.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0146.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0148.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0154.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0158.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0161.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0165.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0168.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/DSC0169.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/grundriss2.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/ansicht7.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/ansicht2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/ansicht5.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/ansicht6.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/Koje-31-EEF-Schild.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/perspektive2.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab November 2020 kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 30","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje30","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8398.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8399.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8400.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8401.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8402.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8403.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8404.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8405.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8406.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8407.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8408.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8409.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-8410.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/grundir.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/ansicht2.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/Koje-30-Energieeffizienz-Web.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/perspektive2.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":"Einbauküche von Nolte, Rot Hochglanz, ca. 470 cm, Inklusive  Panorama-­Kochstelle & Elektrogeräte von Siemens."},"manufacturer":"Logo Nolte"},{"title":"Koje 45","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje45","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8327.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8328.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8330.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8331.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8332.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8333.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8340.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8334.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8335.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8329.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8336.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8337.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8338.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8339.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8342.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8343.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/IMG-8344.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/Ansicht-2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/Ansicht-3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/Koje-45-Energieeffizienz-Web.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-45\/Perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 50","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje50","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0541.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0523.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0518.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0519.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0526.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0527.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0529.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0531.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0533.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0535.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0537.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/DSC0547.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/Ansicht-2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/Ansicht-3.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/Ansicht-4.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/Koje50e.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-50\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 5","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje5-noh","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7656.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7664.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7660.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7665.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7666.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7667.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7668.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7669.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7670.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/IMG-7671.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/ansicht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/ansicht2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/ansicht3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/Koje-5-Energieeffizienz-Web2.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-5\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 9","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje9-noh","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7706.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7707.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7708.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7709.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7710.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7711.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7712.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7713.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7714.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/IMG-7715.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/ansicht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/ansicht2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/ansicht3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-Energieeffizienz3.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 10","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje10-noh","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/IMG-7725.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/IMG-7726.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/IMG-7727.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/IMG-7728.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/IMG-7729.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/IMG-7730.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/IMG-7731.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/IMG-7732.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/ansicht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/ansicht2.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/Koje-10-Energieeffizienz-Web.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-10\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 11","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje11-noh","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/IMG-7716.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/IMG-7717.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/IMG-7718.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/IMG-7719.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/IMG-7720.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/IMG-7721.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/IMG-7722.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/IMG-7723.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/ansciht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/ansicht2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/ansicht3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/Koje-11-Energieeffizienz2.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-11\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 16","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje16-noh","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/IMG-7754.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/IMG-7757.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/IMG-7755.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/IMG-7756.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/IMG-7758.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/IMG-7759.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/IMG-7760.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/IMG-7761.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/IMG-7762.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/ansciht2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/ansicht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/ansicht3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-Energieeffizienz-Web.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":"Einbauküche von Burger, Schwarz matt, Limette, ca. 235 x 347 cm, Inklusive ELICA Dunstabzugshaube & Inklusive Elektrogeräte von Neff."},"manufacturer":"hartmut geugies 2017 2"},{"title":"Koje 17","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje17-noh","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/IMG-7773.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/IMG-7776.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/IMG-7774.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/IMG-7775.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/IMG-7777.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/IMG-7778.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/ansicht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/ansicht2.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/Koje-17-Energieeffizienz-Web.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-17\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":"Einbauküche von Burger, Weiß glänzend, ca. 310 x 235 cm, Inklusive Blanco Spüle & Elektrogeräte von Siemens."},"manufacturer":"phillip bajor"},{"title":"Koje 27","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje27-noh-2","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9990.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9992.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9993.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9994.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9995.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9996.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9997.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9998.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9999.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-0002.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-0003.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-0004.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-0005.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-0006.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-0007.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-0008.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/IMG-0010-neu.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/grundriss8.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/ansicht14.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/koje27.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/perspektive8.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 28","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje28-noh-2","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-7781.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-7782.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-7783.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-7785.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-7784.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/IMG-7786.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/ansciht.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-28\/koje28.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-18\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":"Einbauküche von Burger, Beton dunkel NB, ca. 303 cm, Inklusive Edelstahl Rückwand &  Elektrogeräte von BURG."},"manufacturer":"Burger K\u00fcchen"},{"title":"Koje 29","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje29-noh","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7857.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7860.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7862.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7861.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7859.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7858.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7863.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7864.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7865.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/IMG-7866.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/ansciht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/ansicht2.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/Koje-29-Energieeffizienz-Web3.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-29\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":"Einbauküche von Bauformat, Weiß Lack, ca. 295 x 385 cm, Inklusive Blanco Spüle & Elektrogeräte von Neff."},"manufacturer":"fatma esen 2017"},{"title":"Koje 30","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje30-noh","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-7772.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-7766.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-7767.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-7768.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-7769.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-7770.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-7771.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/IMG-7773.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/ansciht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/ansicht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/ansicht4.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/Koje-30-Energieeffizienz-Web2.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-30\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":"Einbauküche von Burger, Sandbeige glänzend, ca. 240 x 295 cm, Inklusive Blanco Spüle & Elektrogeräte von Neff."},"manufacturer":"Burger K\u00fcchen"},{"title":"Koje 31","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje31-noh","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/IMG-7788.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/IMG-7789.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/IMG-7790.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/IMG-7792.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/IMG-7791.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/IMG-7793.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/IMG-7794.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/IMG-7796.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/IMG-7797.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/grundriss.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/ansciht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/ansicht4.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/ansicht.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/ansciht2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/ansicht3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/Koje-31-Energieeffizienz2.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-31\/perspektive.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im November 2018 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 33","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje33-noh-2","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9918.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9917.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9920.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9922.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9924.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/IMG-9925.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/grundriss10.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/ansicht18.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/ansicht19.jpg","\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/ansicht20.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/koje33.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/New-Kitchen\/perspektive10.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im  in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":""}},{"title":"Koje 9","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje9p","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-01.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-02.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-03.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-04.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-05.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-06.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-07.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-08.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-09.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-10.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje-9-11.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Grundriss-1.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje9-20-04.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje9-20-04-2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje9-20-04-3.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje9-20-04-4.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Koje9-20-04-5.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/koje9p.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Perspektive-1.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-9\/Perspektive-2.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im Dezember 2017 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 16","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje16p","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-01.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-02.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-03.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-04.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-05.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-06.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-07.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-08.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-09.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-10.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje-16-11.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje16-P-Grundriss-2017-10-20.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje16-P-Ansicht2-2017-10-20.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje16-20-04.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje16-20-04-2.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/koje16p.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-16\/Koje16-P-3D-2017-10-20.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im Dezember 2017 in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":"Zeilenküche von Burger, Susann schwarz Hochglanz, ca. 340 + 305 cm, Inklusive Elektrogeräten von AEG, Siemens + elica & LED Beleuchtung."},"manufacturer":"manuela frahn"},{"title":"Koje 22","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje22p-2","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Koje-22-01-.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Koje-22-03.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Koje-22-04.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Koje-22-02.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Grundriss-1.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Ansicht-1.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Ansicht-2.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/koje22p.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Perspektive-1.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-22\/Perspektive-2.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im Dezember 2017 in unserem K\u00fcchenstudio montiert.\nDiese K\u00fcche steht noch in unserer Ausstellung. Die Ausstellungsk\u00fcche ist aber bereits verkauft.","teaser":"Zeilenküche von Bauformat, Stone Art Grauschiefer NB, ca. 275 + 310 cm, Inklusive Elektrogeräten von Constructa + Elica & LED-Beleuchtung"},"manufacturer":"Andreas Jauer"},{"title":"Koje 23","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje23p","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje-23-01.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje-23-02.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje-23-03.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje-23-04.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje-23-05.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje23-P-Grundriss-2017-10-20.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje23-P-Ansicht-2017-10-20.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje23-20-5.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje23-20-04-3.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje23-EEK-2017-11-22.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-23\/Koje23-P-3D-2017-10-20.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im Dezember 2017 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 24","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje24p","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje-24-01.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje-24-02.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje-24-03.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje-24-04.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje-24-05.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje-24-06.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje24-P-Grundriss-2017-10-20.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje24-P-Ansicht2-2017-10-20.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje24-P-Ansicht-2017-10-20.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje24-EEK-2017-11-22.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-24\/Koje24-P-3D-2017-10-20.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im Dezember 2017 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 26","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje26p","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje-26-01.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje-26-02.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje-26-03.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje-26-04.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje26-P-Grundriss-2017-10-20.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje26-P-Ansicht3-2017-10-20.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje26-P-Ansicht2-2017-10-20.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje26-P-Ansicht-2017-10-20.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje26-EEK-2017-11-22.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-26\/Koje26-P-3D-2017-10-20.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im Dezember 2017 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 27","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/Koje-27p","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-2.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-3.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-03.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-04.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-05.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-06.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-07.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-08.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Grundriss-1.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Ansicht-1.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Ansicht-2.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-Energieeffizienz.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Perspektive-1.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Perspektive-2.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im Dezember 2017 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 37","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje37p-2","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-01.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje-27-02.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-37\/Koje37-20-04-6.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-37\/Koje37-20-6.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-27\/Koje27-EEK-2017-11-22.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-37\/Koje37-20-04-5.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im Dezember 2017 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null},{"title":"Koje 38","url":"https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje37p","images":{"gallery":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-38\/Koje-38-02.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-38\/Koje-38-01.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-38\/Koje-38-04.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-38\/Koje-38-03.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-38\/Koje-38-05.jpg"],"floorplan":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-37\/Koje37-P-Grundriss-2017-10-20.jpg"],"views":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-37\/Koje37-P-Ansicht2-2017-10-20.jpg","\/assets\/Uploads\/Ausstellungskuechen\/Koje-37\/Koje37-P-Ansicht-2017-10-20.jpg"],"energy":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-38\/Koje-38-Energieeffizienz.jpg"],"3d":["\/assets\/Uploads\/Ausstellungskuechen\/Koje-37\/Koje37-P-3D-2017-10-20.jpg"]},"texts":{"note":"Diese Ausstellungsk\u00fcche wurde im Dezember 2017 in unserem K\u00fcchenstudio montiert.\nSie k\u00f6nnen die K\u00fcche als Ausstellungsst\u00fcck ab sofort kaufen. <br>","teaser":""},"manufacturer":null}]
    EOD;

        $data = json_decode($json, true);

        d($data);

        foreach ($data as $productData) {

            $product = new \Frootbox\Ext\Core\ShopSystem\Persistence\Product([
                'pluginId' => $this->plugin->getId(),
                'pageId' => $this->plugin->getPageId(),
                'datasheetId' => 86,
                'title' => $productData['title'],
                'config' => [
                    'xdata' => $productData,
                ],
            ]);

            $product = $productsRepository->insert($product);

            $text = $texts->fetchByUid($product->getUid('teaser'), [ 'createOnMiss' => true ]);
            $text->setText($productData['texts']['teaser']);
            $text->save();

            $text = $texts->fetchByUid($product->getUid('text-note'), [ 'createOnMiss' => true ]);
            $text->setText($productData['texts']['note']);
            $text->save();
        }

        die("Set texts");
        exit;



        $json = <<<'EOD'
    ["https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje15","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje18","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje22","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje29","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje31","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje30","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje45","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje50","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje5-noh","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje9-noh","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje10-noh","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje11-noh","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje16-noh","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje17-noh","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje27-noh-2","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje28-noh-2","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje29-noh","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje30-noh","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje31-noh","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje33-noh-2","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje9p","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje16p","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje22p-2","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje23p","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje24p","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje26p","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/Koje-27p","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje37p-2","https:\/\/moebel-luebbering.de\/produkte\/abverkauf\/kuechen\/show\/koje37p"]
    EOD;

        $data = json_decode($json, true);

        $list = [];

        foreach ($data as $url) {

            $bunk = [
                'title' => null,
                'url' => $url,
                'images' => [],
                'texts' => []
            ];

            $source = file_get_contents($url);

            $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($source);


            $crawler->filter('#zoom-thumbnails div[data-link]')->each(function ( $element ) use (&$bunk) {
                $bunk['images']['gallery'][] = $element->getAttribute('data-link');
            });

            $crawler->filter('.col-md-5.col-xs-12.pull-right h3')->each(function ( $element ) use (&$bunk) {
                $bunk['title'] = $element->getInnerHtml();
            });

            $crawler->filter('.details .note')->each(function ( $element ) use (&$bunk) {
                $bunk['texts']['note'] = trim($element->getInnerHtml());
            });

            $crawler->filter('.details')->each(function ( $element ) use (&$bunk) {

                $source = (string) $element;
                $da = explode('<br>
<br>
<hr>', $source);
                $da = explode('<br>
<hr>
<div class="note">', $da[1]);

                $bunk['texts']['teaser'] = trim($da[0]);
            });


            $crawler->filter('#Grundriss a')->each(function ( $element ) use (&$bunk) {

                $bunk['images']['floorplan'][] = $element->getAttribute('href');
                // $bunk['texts']['note'] = trim($element->getInnerHtml());
            });


            $crawler->filter('#Ansichten a')->each(function ( $element ) use (&$bunk) {

                $bunk['images']['views'][] = $element->getAttribute('href');
                // $bunk['texts']['note'] = trim($element->getInnerHtml());
            });


            $crawler->filter('#Energieeffizienz a')->each(function ( $element ) use (&$bunk) {
                $bunk['images']['energy'][] = $element->getAttribute('href');
            });

            $crawler->filter('#3D-Darstellung a')->each(function ( $element ) use (&$bunk) {
                $bunk['images']['3d'][] = $element->getAttribute('href');
            });

            $crawler->filter('.details img')->each(function ( $element ) use (&$bunk) {
                $bunk['manufacturer'] = $element->getAttribute('alt');
            });

            $list[] = $bunk;
        }


        d(json_encode($list));


        $data = ["https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje15", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje15", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje18", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje18", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje22", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje22", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje29", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje29", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje31", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje31", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje30", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje30", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje45", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje45", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje50", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje50", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje5-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje5-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje9-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje9-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje10-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje10-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje11-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje11-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje16-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje16-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje17-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje17-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje27-noh-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje27-noh-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje28-noh-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje28-noh-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje29-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje29-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje30-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje30-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje31-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje31-noh", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje33-noh-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje33-noh-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje9p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje9p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje16p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje16p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje22p-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje22p-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje23p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje23p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje24p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje24p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje26p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje26p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/Koje-27p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/Koje-27p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje37p-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje37p-2", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje37p", "https://moebel-luebbering.de/produkte/abverkauf/kuechen/show/koje37p"];
        $list = [];

        foreach ($data as $url) {

            if (in_array($url, $list)) {
                continue;
            }

            $list[] = $url;
        }

        d(json_encode($list));



        */
    }
}
