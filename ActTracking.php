<?php

namespace SiteApp\Tracking;

use App\Action;
use Module;
use Package\Pkg\Package;
use Punkt\Pvz\Punkt;
use Tracking\TrackingCode\GlavpunktTrackCode;
use Tracking\TypeTracking\PackageTrackingByTemplate;

/**
 * Страница трекинга заказа
 *
 * @author SokolovMikhail
 */
class ActTracking extends Module implements Action
{
    public function handle()
    {
        $this->twigTpl = "Tracking/tracking.twig";

        $trackCode = explode('/', $GLOBALS['_SERVER']['REQUEST_URI'])[2];

        if ($trackCode) {
            $pkg = (new Package((new GlavpunktTrackCode)->decode($trackCode)));
            $punkts = (new PackageTrackingByTemplate($pkg))->statuses();
            foreach ($punkts as &$punkt) {
                if (isset($punkt['pvz_id'])) {
                    $info = (new Punkt($punkt['pvz_id']))->info();
                    $punkt['full_address'] = 'г. ' . $info['city'] . ', ' . $info['address'];
                }

                $punkt['date'] = isset($punkt['create_date']) ? $punkt['create_date'] : false;

                $punkt['status'] = [
                    'created' => 'Заведен в систему',
                    'accepted' => 'Принят в Главпункт',
                    'to_terminal' => 'В пути на терминал',
                    'at_terminal' => 'Поступил на терминал',
                    'to_pvz' => 'В пути на ПВЗ выдачи',
                    'at_pvz' => 'Поступил в ПВЗ выдачи',
                    'completed' => 'Заказ выдан / Доставлен',
                    'to_return' => 'Заказ поставлен на возврат',
                    'returned' => 'Заказ возвращен'
                ][$punkt['code']];
            }

            $package = $pkg->info();
            $this->twigVars['package'] = $pkg->info();
            $this->twigVars['endPunktInfo'] = (new Punkt($package['punkt_id']))->info();
            $this->twigVars['punkts'] = $punkts;
        }
    }
}
