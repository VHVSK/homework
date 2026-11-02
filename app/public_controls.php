<?php
declare(strict_types=1);

function public_tools_head(): void {
    ?><link rel="stylesheet" href="<?=e(asset_url('sharing.css'))?>"><script defer src="<?=e(asset_url('sharing.js'))?>"></script><?php
    pwa_head();
    ?><link rel="stylesheet" href="<?=e(asset_url('readability.css'))?>"><?php
}

function period_fields(array $filters): void {
    ?><div class="period-filters"><div class="field"><label for="filter-period">Період добірки</label><select id="filter-period" name="period"><?php foreach(['all'=>'Весь час','day'=>'За день','week'=>'За тиждень'] as $value=>$label):?><option value="<?=$value?>"<?=$filters['period']===$value?' selected':''?>><?=e($label)?></option><?php endforeach?></select></div><div class="field"><label for="filter-date">Дата або день потрібного тижня</label><input id="filter-date" type="date" name="date" value="<?=e($filters['date'])?>" min="1900-01-01" max="9999-12-31"></div><div class="field"><label for="filter-date-by">Відбирати завдання</label><select id="filter-date-by" name="date_by"><option value="published"<?=$filters['date_by']==='published'?' selected':''?>>За датою публікації</option><option value="due"<?=$filters['date_by']==='due'?' selected':''?>>За строком виконання</option></select></div><p class="period-note">Тиждень — від понеділка до неділі. За строком виконання відбираються лише завдання із зазначеним строком. Натисніть «Знайти завдання», щоб застосувати період.</p></div><?php
}

function share_panel(string $shareUrl, string $title, ?string $printUrl=null, bool $single=false): void {
    ?><div class="share-panel no-print" data-share-panel aria-label="Поширення завдань">
        <div class="share-actions">
            <button class="btn btn-secondary" type="button" data-copy-url="<?=e($shareUrl)?>" title="Скопіювати посилання" aria-label="Скопіювати посилання" hidden><?=icon('link',16)?><span class="share-button-label" data-copy-label>Скопіювати</span></button>
            <?php if($single):?><button class="btn btn-secondary" type="button" data-print title="Друк або збереження у PDF" aria-label="Друк або збереження у PDF" hidden><?=icon('print',16)?><span class="share-button-label">Друк</span></button><?php elseif($printUrl):?><a class="btn btn-secondary" href="<?=e($printUrl)?>" target="_blank" rel="noopener noreferrer" title="Друк добірки або збереження у PDF" aria-label="Друк добірки або збереження у PDF"><?=icon('print',16)?><span class="share-button-label">Друк</span></a><?php endif?>
        </div>
        <span class="sr-only" role="status" aria-live="polite" data-share-status></span>
    </div><?php
}
