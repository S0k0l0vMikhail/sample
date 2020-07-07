<?php

namespace SiteApp\Sales;

use App\Action;
use Client\Client;
use ClientManager;
use Exception;
use Good\GoodsList\GoodsListStd;
use Module;
use Punkt\PunktsList\PunktsListStd;
use Punkt\Pvz\Punkt;

/**
 * Выгрузить остатки товара в CSV
 *
 * @author SokolovMikhail
 */
class ActOstatkiCsv extends Module implements Action
{
    public function handle()
    {
        $allGoods = [];

        $fields = [
            'cities_id' => 'SPB',
            'enabled' => 1,
            'priemka_enabled' => 1,
            'virtual' => 0
        ];

        $client_info = (new ClientManager)->getClientInfoByLogin($_SESSION['login']);
        $punkts = [];

        foreach (new PunktsListStd($fields, $orderBy = [], 'AND') as $punkt) {
            $punktInfo = $punkt->info();
            $punkts[$punktInfo['id']] = $punktInfo;
        }

        $allPunkts = array_merge(
            [
                'Fulfillment-V11' => (new Punkt('Fulfillment-V11'))->info(),
                'Msk-Fulfillment' => (new Punkt('Msk-Fulfillment'))->info()
            ],
            $punkts
        );

        foreach ($allPunkts as $punkt) {
            $goodsList = (new GoodsListStd())->availability(true)->punkt(new Punkt($punkt['id']))
                ->client(new Client($client_info['id']));
            $goods = [];
            foreach ($goodsList as $i => $g) {
                $goods[$i] = $g->info();
                $goods[$i]['punkt_id'] = $punkt['id'];
            }
            $allGoods = array_merge($allGoods, $goods);
        }

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename=ostatki-' . date('H-i-d-m-Y') . '.csv');
        header('Pragma: no-cache');

        $h = fopen('php://output', 'w+');
        stream_filter_append($h, 'convert.iconv.utf-8/windows-1251', STREAM_FILTER_WRITE);

        if ($h === false) {
            throw new Exception("Не удалось открыть файл csv для записи");
        }

        $row = array();
        $row[0] = 'Артикул';
        $row[1] = 'Наименование';
        $row[2] = 'Цена';
        $row[3] = 'Количество';
        $row[4] = 'Пункт';
        fputcsv($h, $row, ';');

        foreach ($allGoods as $g) {
            $row = array();
            $row[0] = $g['sku'];
            $row[1] = $g['name'];
            $row[2] = $g['price'][$g['punkt_id']];
            $row[3] = $g['ostatok'][$g['punkt_id']];
            $row[4] = $g['punkt_id'];
            fputcsv($h, $row, ';');
        }

        return '';
    }
}
