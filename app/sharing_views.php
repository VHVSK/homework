<?php
declare(strict_types=1);

function print_view(): void
{
    $filters = public_filters($_GET);
    $demo = isset($_GET['demo']) && $_GET['demo'] === '1';
    if (!collection_ready($filters)) {
        layout_start('Добірка завдань', 'home', true);
        ?><div class="empty-state"><h1>Спершу оберіть школу та клас</h1><p>Після цього можна підготувати всі завдання класу або одного предмета за день, тиждень чи весь час.</p><a class="btn btn-primary" href="<?=e(share_url($filters, $demo))?>">Обрати школу й клас</a></div><?php
        layout_end();
        return;
    }
    if ($demo) {
        if ($filters['school'] !== 1 || $filters['class'] !== 1 || ($filters['city'] !== '' && $filters['city'] !== 'Київ') || !in_array($filters['subject'], [0, 1, 2, 3], true)) {
            throw new HttpError(404, 'Демонстраційний клас або предмет не знайдено.');
        }
        $subjects = [0=>'Усі предмети', 1=>'Математика', 2=>'Українська мова', 3=>'Англійська мова'];
        $context = ['school_name'=>'Ліцей «Світанок»','city'=>'Київ','class_name'=>'7-А','subject_name'=>$subjects[$filters['subject']]];
        $items = array_values(array_filter(demo_data($filters)['items'], static fn(array $row): bool => homework_matches_period($row, $filters)));
    } else {
        $context = collection_context($filters);
        if (!$context) throw new HttpError(404, 'Клас або предмет цієї школи недоступний.');
        $items = public_collection($filters);
    }
    layout_start('Добірка завдань', 'home', true,collection_print_name($context,$filters,$items));
    ?><div class="collection-actions no-print"><a class="btn btn-secondary" href="<?=e(share_url($filters, $demo))?>">← До добірки</a><button type="button" class="btn btn-primary" data-print>Друк / Зберегти PDF</button><button type="button" class="btn btn-secondary" data-share-url="<?=e(share_url($filters, $demo))?>" data-share-title="<?=e('Завдання · '.$context['class_name'].' · '.$context['school_name'])?>">Поділитися посиланням</button><p class="small text-muted">У вікні друку оберіть «Зберегти як PDF». До документа входять усі завдання добірки, включно з іншими сторінками списку.</p><p role="status" aria-live="polite" data-share-status></p></div>
    <section class="print-collection"><header class="print-heading"><p class="eyebrow">Після дзвоника · Допоміжна дошка завдань</p><h1>Домашні завдання · <?=e($context['class_name'])?></h1><p><?=e($context['city'].' · '.$context['school_name'])?></p><p><strong><?=e($context['subject_name'])?></strong> · <?=e(selection_summary($filters))?></p><?php if($filters['q']!==''):?><p>Пошук: <?=e($filters['q'])?></p><?php endif?><p class="small text-muted">Завдань: <?=count($items)?>. Сформовано <?=e(date('d.m.Y H:i'))?>.<?php if($demo):?> Демонстраційні дані.<?php endif?></p></header>
    <?php if (!$items):?><p class="notice notice-info">За вибраними фільтрами опублікованих завдань немає. Якщо вибрано строк виконання, завдання без зазначеного строку сюди не входять.</p><?php else:
        $groups=[];
        foreach($items as $item) $groups[$item['subject_name']][]=$item;
        foreach($groups as $subject=>$rows):?><section class="print-subject"><h2><?=e($subject)?></h2><?php foreach($rows as $item):?><article class="print-homework"><h3><?=e($item['title'])?></h3><div class="print-homework-meta"><span>Учитель: <?=e($item['teacher_name'])?> · Опубліковано <?=e(pretty_date($item['published_date']))?></span><span class="due-date deadline-badge<?=$item['due_date']?'':' deadline-badge-empty'?>"><span class="deadline-copy"><?php if($item['due_date']):?><span class="deadline-label">Виконати до</span><time class="deadline-value" datetime="<?=e($item['due_date'])?>"><?=e(pretty_date($item['due_date']))?></time><?php else:?><span class="deadline-label">Без строку виконання</span><?php endif?></span></span></div><div class="rich-text"><?=clean_rich_html($item['body_html'])?></div></article><?php endforeach?></section><?php endforeach;
    endif;?><footer class="print-source"><p>Актуальні завдання та зміни дивіться на сайті:</p><a href="<?=e(share_url($filters, $demo))?>"><?=e(share_url($filters, $demo))?></a><p>Матеріали є публічними. Ця добірка не є офіційним класним журналом.</p></footer></section><?php
    layout_end();
}
