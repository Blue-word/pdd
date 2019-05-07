<?php
namespace app\admin\controller;

class Test1
{
   

    public function index()
    {
        $data_list = $data = array();
        // $ret = D('ProduceProject')->getCostBatchListBySku(array($sku));      //plm获取大货成本信息
        // if(!$ret['code'] && $ret['data']){
        //     foreach ($ret['data'][$sku]['material_list'] as $key => $value) {
        //         $material_list[$value['material_sku']] = $value;
        //     }
        // }
        $formartInfo = array('XS','XXS');
        // $res = D('ProduceProject')->getMaterialListBySkuSize([$sku=>array_keys($formartInfo)]);      //plm获取大货bom信息
        $json_list = '{"code":"0","msg":"接口请求成功","info":{"tee150727130":{"info":{"id":"48549","sku":"tee150727130","year":"2018","design_code":"M02XM180514002","color_id":"808","version_code":"1","brand_id":"1001","band_id":"4048","category_id":"170010202","designer_id":"徐明","size_type_id":"clothes_letter","color_name":"白花灰","band_name":"0508","brand_name":"shein","category_name":"短袖T恤","size_type_list":["XXS","XS","S","M","L","XL","XXL","XXXL"]},"XXS":[{"id":"201615","material_items_name":"成衣","use_area_name":"抽绳","material_sku":"MSQXT0001SB黑色","material_title":"MES测试勿删A","material_color_id":"黑色","material_color_name":"黑-小黑","composition_name":"T:%","unit_name":"条","supplier_name":"3A织带","supplier_code":"001","supplier_color_code":"黑色A","secondary_process_name":[],"process_remark":"","single_amount":"1.20","material_status":"","supplier_tel":"15918760365","supplier_address":"轻纺城负一层四街UG1391档","purchase_type":"","purchase_type_name":"是","valid_width":"","width":"150","weight":"30","single_amount_kg":26.67},{"id":"201618","material_items_name":"辅料","use_area_name":"帽子","material_sku":"FNYXV0001SB咖啡色","material_title":"MES测试勿删C","material_color_id":"咖啡色","material_color_name":"黑-小黑","composition_name":"V:%","unit_name":"条","supplier_name":"007纽扣","supplier_code":"007纽扣1","supplier_color_code":"007纽扣3","secondary_process_name":[],"process_remark":"","single_amount":"1.40","material_status":"","supplier_tel":"15918760366","supplier_address":"轻纺城负一层二街UG1158档","purchase_type":"","purchase_type_name":"否","valid_width":"","width":"170","weight":"14","single_amount_kg":58.82},{"develop_batch_extend_id":"201616","material_items_name":"包装辅料","use_area_name":"领-下摆","original_material_sku":"MSNZV0001SB咖啡色","single_amount":"1.30","material_sku":"","material_title":"","material_color_id":"","material_color_name":"","unit_name":"","supplier_name":"","supplier_code":"","supplier_color_code":"","material_status":"","supplier_tel":"","process_remark":"","supplier_address":"","purchase_type":"","purchase_type_name":"","valid_width":"","width":"","weight":"","single_amount_kg":""}],"XS":[{"id":"201615","material_items_name":"成衣","use_area_name":"抽绳","material_sku":"MSQXT0001SB黑色","material_title":"MES测试勿删A","material_color_id":"黑色","material_color_name":"黑-小黑","composition_name":"T:%","unit_name":"条","supplier_name":"3A织带","supplier_code":"001","supplier_color_code":"黑色A","secondary_process_name":[],"process_remark":"","single_amount":"1.20","material_status":"","supplier_tel":"15918760365","supplier_address":"轻纺城负一层四街UG1391档","purchase_type":"","purchase_type_name":"是","valid_width":"","width":"150","weight":"30","single_amount_kg":26.67},{"id":"201618","material_items_name":"辅料","use_area_name":"帽子","material_sku":"FNYXV0001SB咖啡色","material_title":"MES测试勿删C","material_color_id":"咖啡色","material_color_name":"黑-小黑","composition_name":"V:%","unit_name":"条","supplier_name":"007纽扣","supplier_code":"007纽扣1","supplier_color_code":"007纽扣3","secondary_process_name":[],"process_remark":"","single_amount":"1.40","material_status":"","supplier_tel":"15918760366","supplier_address":"轻纺城负一层二街UG1158档","purchase_type":"","purchase_type_name":"否","valid_width":"","width":"170","weight":"14","single_amount_kg":58.82},{"develop_batch_extend_id":"201616","material_items_name":"包装辅料","use_area_name":"领-下摆","original_material_sku":"MSNZV0001SB咖啡色","single_amount":"1.30","material_sku":"","material_title":"","material_color_id":"","material_color_name":"","unit_name":"","supplier_name":"","supplier_code":"","supplier_color_code":"","material_status":"","supplier_tel":"","process_remark":"","supplier_address":"","purchase_type":"","purchase_type_name":"","valid_width":"","width":"","weight":"","single_amount_kg":""}]}},"error":{}}';
        $json_data = json_decode($json_list,true);
        $res['code'] = 0;
        $res['data'] = $json_data['info'];
       	$category = 4;
        $res_1 = $this->uniqueBomSku($res['data'],$formartInfo,$category,$material_list);
        // print_r($res);die;
        die;
        $info_data = $res_1[$sku]['info'];
        foreach ($res_1[$sku]['material_list'] as $index => $info) {
            $size_need_total = array_map(function ($v) {
                return $v['need_num'];
            }, $info['size_num']);
            $size_num_total = array_sum($size_need_total);     //该物料sku总需用量
            $data[$index] = array(
                'material_sku' => $info['material_sku'],
                'supplier' => $info['supplier_name'],
                'material_name' => $info['material_title'],
                'material_color' => $info['material_color_name'],
                'unit' => $info['unit_name'],
                'size_num' => $info['size_num'],
                'two_process' => $info['two_process'],
                'wastage' => $info['supplier_loss'],
                'need_num' => round($size_num_total * (1+$info['supplier_loss']/100), 2),
            );
        }
        var_dump($data);
        if ($data) {
            return array(
                'code' => 1,
                'data' => $data,
                'info' => $info_data,
            );
        }else{
            return array(
                'code' => 0,
                'msg' => '单件用量（kg）获取为空',
            );
        }
    }

    /**
     * 大货bom数据去重处理
     * @author 转自CD 2018-05-16 16:38:23
     * @modify 蓝勇强 新增损耗，新cmt单过滤
     */
    public function uniqueBomSku($bom, $formartInfo,$category,$material_list)
    {
    	// var_dump($bom);
        $return = [];
        foreach ($bom as $goods_sku => $skuInfo) {
        	// var_dump($skuInfo);
            foreach ($skuInfo as $index => $value) {
            	// var_dump($index);
            	// var_dump($value);
                if ($index == 'info') {//数组中包含的是大货sku的信息
                    $return[$goods_sku]['info'] = $value;
                } else {//index是尺码，数组是这个尺码对应的物料信息
                    foreach ($value as $materialInfo) {
                    	// var_dump($materialInfo);
                        //取对应物料sku的供应商损耗
                        // foreach ($material_list as $sku => $cost) {
                        //     $supplier_loss = 0;
                        //     if($sku == $materialInfo['material_sku']){
                        //         $price_loss = $cost['supplier_loss'] ? $cost['supplier_loss'] : 0;
                        //         $supplier_loss = $price_loss;   //损耗
                        //         break;
                        //     }
                        // }
                        //新cmt单过滤非自购
                        if ($category == 7 && $materialInfo['purchase_type_name'] == '否') {
                            continue;
                        }
//                        if (in_array($materialInfo['material_sku'], ['16FLXH00157009', '16FLXH00159014', '16FLGF04159014'])) {
//                            //透明条，不进行采购，工厂自己采
//                            continue;
//                        }

                        //由于供应链后台boom数据中的二次工艺数据都是备注，而且业务方也没有整理，所以这边只能通过
                        //汉字判断是否含数码印花，其他的二次工艺全部过滤掉，公司现在只做数码印花的二次工艺
                        if (in_array('数码印花', $materialInfo['secondary_process_name'])) {
                            $two_process = '数码印花';
                        } else {
                            $two_process = '';
                        }

                        /**
                         * 业务基础信息维护不对，开后门放开限制
                         */
//                        if(empty($materialInfo['material_sku']) || empty($materialInfo['supplier_name'])){
//                            unset($return[$goods_sku]);
//                            break 2;
//                        }

                        /**
                         * 物料sku为空的过滤掉，可能会导致系统数据以及业务流程混乱
                         */
                        //@TODO 等业务方吧基础数据维护好，这边的代码要去掉
                        if (empty($materialInfo['material_sku'])) {
                            //没有物料sku的过滤掉
                            continue;
                        }
                        // var_dump($return[$goods_sku]['material_list']);
                        // var_dump($return[$goods_sku]['material_list']);
                        $return[$goods_sku]['material_list'][$materialInfo['material_sku'] . '_' . $two_process] = $materialInfo;
                        // if (!array_key_exists($materialInfo['material_sku'] . '_' . $two_process, $return[$goods_sku]['material_list'])) {
                        //     $return[$goods_sku]['material_list'][$materialInfo['material_sku'] . '_' . $two_process] = $materialInfo;
                        // }
                        $return[$goods_sku]['material_list'][$materialInfo['material_sku'] . '_' . $two_process]['two_process'] = $two_process;
                        $return[$goods_sku]['material_list'][$materialInfo['material_sku'] . '_' . $two_process]['size_num'][$index]['size'] = $index;
                        $return[$goods_sku]['material_list'][$materialInfo['material_sku'] . '_' . $two_process]['size_num'][$index]['single_cost'] += $materialInfo['single_amount'];
                        $return[$goods_sku]['material_list'][$materialInfo['material_sku'] . '_' . $two_process]['size_num'][$index]['order_num'] = $formartInfo[$index];
                        //对应物料sku的供应商损耗
                        $return[$goods_sku]['material_list'][$materialInfo['material_sku'] . '_' . $two_process]['supplier_loss'] = $supplier_loss;
                        var_dump($return);
                    }
                }
            }
        }

        //统计每个物料对应尺码的总订单数量s
        foreach ($return as $goods_sku => &$skuInfo) {
            foreach ($skuInfo['material_list'] as $index => &$value) {
                foreach ($value['size_num'] as $size => &$sizeInfo) {
                    $sizeInfo['need_num'] = round($sizeInfo['single_cost'] * $sizeInfo['order_num'], 2);
                }
            }
        }

        //注销掉，防止引用错误
        unset($skuInfo);
        unset($value);
        unset($sizeInfo);

        return $return;
    }


}

