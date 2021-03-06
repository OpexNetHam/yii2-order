<?php
use yii\helpers\Html;
use yii\grid\GridView;
use pistol88\order\widgets\Informer;
use kartik\export\ExportMenu;

$this->title = yii::t('order', 'Orders');
$this->params['breadcrumbs'][] = $this->title;

use pistol88\order\assets\Asset;
Asset::register($this);

if($dateStart = yii::$app->request->get('date_start')) {
    $dateStart = date('Y-m-d', strtotime($dateStart));
}

if($dateStop = yii::$app->request->get('date_stop')) {
    $dateStop = date('Y-m-d', strtotime($dateStop));
}

$columns = [];

$columns[] = ['attribute' => 'id', 'filter' => false, 'options' => ['style' => 'width: 49px;']];

$columns[] = [
    'attribute' => 'count',
    'label' => yii::t('order', 'Cnt'),
    'content' => function($model) {
        return $model->count;
    }
];

$columns[] = [
    'attribute' => 'cost',
    'label' => yii::$app->getModule('order')->currency,
    'content' => function($model) {
        $total = $model->cost;
        if($model->promocode) {
            $total .= Html::tag('div', $model->promocode, ['style' => 'color: orange; font-size: 80%;', yii::t('order', 'Promocode')]);
        }

        return $total;
    },
];

            
foreach(Yii::$app->getModule('order')->orderColumns as $column) {
    if($column == 'payment_type_id') {
        $column = [
            'attribute' => 'payment_type_id',
            'filter' => Html::activeDropDownList(
                $searchModel,
                'payment_type_id',
                $paymentTypes,
                ['class' => 'form-control', 'prompt' => Yii::t('order', 'Payment type')]
            ),
            'value' => function($model) use ($paymentTypes) {
                if(isset($paymentTypes[$model->payment_type_id])) {
                    return $paymentTypes[$model->payment_type_id];
                }
            }
        ];
    } elseif($column == 'shipping_type_id') {
        $column = [
            'attribute' => 'shipping_type_id',
            'filter' => Html::activeDropDownList(
                $searchModel,
                'shipping_type_id',
                $shippingTypes,
                ['class' => 'form-control', 'prompt' => Yii::t('order', 'Shipping type')]
            ),
            'value' => function($model) use ($shippingTypes) {
                if(isset($shippingTypes[$model->shipping_type_id])) {
                    return $shippingTypes[$model->shipping_type_id];
                }
            }
        ];
    } elseif(is_array($column) && isset($column['field'])) {
        $column = [
            'attribute' => 'field',
            'label' => $column['label'],
            'value' => function($model) use ($column) {
                return $model->getField($column['field']);
            }
        ];
    }
    
    $columns[] = $column;
}
            
$columns[] = [
        'attribute' => 'date',
        'filter' => false,
        'value' => function($model) {
            return date(yii::$app->getModule('order')->dateFormat, $model->timestamp);
        }
    ];
        
$columns[] = [
        'attribute' => 'status',
        'filter' => Html::activeDropDownList(
            $searchModel,
            'status',
            yii::$app->getModule('order')->orderStatuses,
            ['class' => 'form-control', 'prompt' => Yii::t('order', 'Status')]
        ),
        'value'	=> function($model) {
            return  Yii::$app->getModule('order')->orderStatuses[$model->status];
        }
    ];
        
$columns[] = ['class' => 'yii\grid\ActionColumn', 'template' => '{view} {update} {delete}',  'buttonOptions' => ['class' => 'btn btn-default'], 'options' => ['style' => 'width: 100px;']];
?>


<div class="main-menu row">
    <div class="col-lg-2">
        <?= Html::a(yii::t('order', 'Create order'), ['create'], ['class' => 'btn btn-success']) ?>
    </div>
    <div class="col-lg-10">
        <?= $this->render('/parts/menu.php', ['active' => 'orders']); ?>
    </div>
</div>

<div class="informer-widget">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title"><?=yii::t('order', 'Statistics');?></h3>
        </div>
        <div class="panel-body">
            <?=Informer::widget();?>
        </div>
    </div>
</div>

<div class="order-index">
    <div class="box">
        <div class="box-body">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><?=yii::t('order', 'Search');?></h3>
                </div>
                <div class="panel-body">
                    <?php if(yii::$app->user->can(current(yii::$app->getModule('order')->adminRoles))) { ?>
                        <form action="" class="row search">
                            <?php
                            foreach(Yii::$app->getModule('order')->orderColumns as $column) {
                                if(is_array($column) && isset($column['field'])) {
                                    ?>
                                    <div class="col-md-2">
                                        <label for="custom-field-<?=$column['field'];?>"><?=$column['label'];?></label>
                                        <input class="form-control" type="text" name="order-custom-field[<?=$column['field'];?>]" value="<?=Html::encode(yii::$app->request->get('order-custom-field')[$column['field']]);?>" id="custom-field-<?=$column['field'];?>" />
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                            <div class="col-md-4">
                                <label><?=yii::t('order', 'Date');?></label>
                                <div style="clear: both;"></div>
                                <input style="width: 180px; float: left;" class="form-control" type="date" name="date_start" value="<?=$dateStart;?>" />
                                <input style="width: 180px;" class="form-control" type="date" name="date_stop" value="<?=$dateStop;?>" />
                            </div>

                            <div class="col-md-2">
                                <label><?=yii::t('order', 'Status');?></label>
                                <select class="form-control" name="OrderSearch[status]">
                                    <option value="">Все</option>
                                    <?php foreach(yii::$app->getModule('order')->orderStatuses as $status => $statusName) { ?>
                                        <option <?php if($status == yii::$app->request->get('OrderSearch')['status']) echo ' selected="selected"';?> value="<?=$status;?>"><?=$statusName;?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <?php if($sellers = yii::$app->getModule('order')->getSellerList()) { ?>
                                <div class="col-md-2">
                                    <select class="form-control" name="OrderSearch[seller_user_id]">
                                        <option value=""><?=yii::t('order', 'Seller');?></option>
                                        <?php foreach($sellers as $seller) { ?>
                                            <option <?php if($seller->id == yii::$app->request->get('OrderSearch')['seller_user_id']) echo ' selected="selected"';?> value="<?=$seller->id;?>"><?=$seller->username;?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            <?php } ?>
                            <div class="col-md-2">
                                <input type="checkbox" <?php if(yii::$app->request->get('promocode')) echo ' checked="checked"'; ?> name="promocode" value="1" id="order-promocode" />
                                <label for="order-promocode"><?=yii::t('order', 'Promocode');?></label>
                                <input class="form-control" type="submit" value="<?=Yii::t('order', 'Search');?>" class="btn btn-success" />
                            </div>
                        </form>
                    <?php } ?>
                </div>
            </div>
            
            <div class="summary row">
                <div class="col-md-6">
                    <?=yii::t('order', 'Total');?>:
                    <?=number_format($dataProvider->query->sum('cost'), 2, ',', '.');?>
                    <?=yii::$app->getModule('order')->currency;?>
                </div>
                <div class="col-md-6 export">
                    <?php
                    $gridColumns = $columns;
                    echo ExportMenu::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => $gridColumns
                    ]);
                    ?>
                </div>
            </div>
            
            <div class="order-list">
                <?=  \kartik\grid\GridView::widget([
                    'export' => false,
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => $columns,
                ]); ?>
            </div>
        </div>
    </div>
</div>
