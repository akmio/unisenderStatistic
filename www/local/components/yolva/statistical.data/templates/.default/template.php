<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

?>
<div class="content">
    <h2><?=$arResult['CONTACT']['EMAIL']?></h2>
    Дата добавления: <?=$arResult['CONTACT']['UF_CREATE_DATE']?><br>
    Статус: <span class="green"><?=$arResult['CONTACT']['UF_EMAIL_STATUS']?></span><br>
    Доступность: <span class="green"><?=$arResult['CONTACT']['UF_EMAIL_AVAILABILITY']?></span><br>
    Последняя отправка: <?=$arResult['CONTACT']['UF_LAST_SEND']?><br>
    Последняя доставка: <?=$arResult['CONTACT']['UF_LAST_RECEIVE']?><br>
    Последнее прочтение: <?=$arResult['CONTACT']['UF_LAST_READ']?><br>
    Последний клик: <?=$arResult['CONTACT']['UF_LAST_CLICK']?><br>
    <br>

    <?if(!empty($arResult['EMAILING_ITEMS'])){?>
        <table>
            <tr><th>Рассылка</th><th>Статус</th><th>Дата добавления</th></tr>
            <?foreach ($arResult['EMAILING_ITEMS'] as $itemEmailing){?>
                <tr><td><?=$itemEmailing['UF_TITLE']?></td><td class="green"><?=$itemEmailing['UF_MAILING_STATUS']?></td><td><?=$itemEmailing['UF_CREATE_DATE']?></td></tr>
            <?}?>
        </table>
    <br>
    <?}?>

    <?if(!empty($arResult['MAIL_ITEMS'])){?>
        <table>
            <tr><th>От кого</th><th>Тема</th><th>Статус</th><th>Дата Создания</th></tr>
            <?foreach ($arResult['MAIL_ITEMS'] as $itemMailing){?>
                <?
                switch ($itemMailing['UF_STATUS']){
                    case 'контакт отписался':
                    case 'временная доставка не удалась':
                    case 'доставка не удалась, попыток отправки больше не будет':
                    case 'отмечено как спам':
                        $status = '<span class="red">'.$itemMailing['UF_STATUS'].'</span>';
                        break;
                    default:
                        $status = '<span class="green">'.$itemMailing['UF_STATUS'].'</span>';
                }
                ?>
                <tr><td><?=$itemMailing['UF_FROM']?></td><td><?=$itemMailing['UF_SUBJECT']?></td><td><?=$status?></td><td><?=$itemMailing['UF_CREATE_DATE']?></td></tr>
            <?}?>
        </table>
        <br>
    <?}?>

</div><?
?>