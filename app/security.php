<?php
declare(strict_types=1);

function input_text(string $key, int $max=200, bool $required=true): string {
    $value=trim(is_string($_POST[$key]??null) ? $_POST[$key] : '');
    if (($required && $value==='') || mb_strlen($value)>$max) throw new InvalidArgumentException('Заповніть поле «'.field_label($key).'» (до '.$max.' символів).');
    return $value;
}
function field_label(string $key): string { return ['name'=>'Ім’я / назва','email'=>'Електронна пошта','city'=>'Місто','title'=>'Тема завдання','address'=>'Адреса','secretary_name'=>'Ім’я уповноваженої особи школи','secretary_email'=>'Пошта уповноваженої особи школи'][$key]??$key; }
function input_id(string $key): int {
    $value=filter_var($_POST[$key]??null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    if (!$value) throw new InvalidArgumentException('Оберіть коректне значення поля.');
    return (int)$value;
}
function email_value(string $key='email', bool $gmail=false): string {
    $value=mb_strtolower(input_text($key,190));
    if (!filter_var($value,FILTER_VALIDATE_EMAIL) || ($gmail && !preg_match('/@gmail\.com$/D',$value))) throw new InvalidArgumentException($gmail?'Для Google-входу вкажіть адресу @gmail.com.':'Вкажіть коректну електронну пошту.');
    return $value;
}
function password_value(string $key='password', bool $required=true): ?string {
    $password=is_string($_POST[$key]??null)?$_POST[$key]:'';
    if (!$required && $password==='') return null;
    if (strlen($password)<12 || strlen($password)>72) throw new InvalidArgumentException('Пароль має містити від 12 до 72 байтів (щонайменше 12 латинських символів).');
    return password_hash($password,PASSWORD_DEFAULT);
}
function date_value(string $key, bool $required=true): ?string {
    $value=input_text($key,10,$required);
    if ($value==='' && !$required) return null;
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
    if (!$date || $date->format('Y-m-d')!==$value) throw new InvalidArgumentException('Вкажіть коректну дату.');
    return $value;
}
function safe_color(?string $color): string { return preg_match('/^#[a-f0-9]{6}$/iD',$color??'')?$color:'#38765b'; }
function clean_rich_html(string $html): string {
    if (strlen($html)>60000) throw new InvalidArgumentException('Текст завдання завеликий. Максимум — 60 КБ.');
    $doc=new DOMDocument('1.0','UTF-8');
    $previous=libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD|LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($previous);
    $allowed=['p','br','strong','b','em','i','u','s','h2','h3','ol','ul','li','blockquote','a'];
    $walk=function(DOMNode $parent) use (&$walk,$allowed): void {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                $tag=strtolower($node->tagName);
                if (in_array($tag,['script','style','iframe','object','embed','svg','math','template','form','input','img','video','audio'],true)) { $parent->removeChild($node); continue; }
                $href=$tag==='a'?trim($node->getAttribute('href')):'';
                $list=$tag==='li'?$node->getAttribute('data-list'):'';
                foreach (iterator_to_array($node->attributes) as $attr) $node->removeAttribute($attr->name);
                if ($tag==='a' && preg_match('~^https?://~i',$href) && filter_var($href,FILTER_VALIDATE_URL)) {
                    $node->setAttribute('href',$href); $node->setAttribute('target','_blank'); $node->setAttribute('rel','noopener noreferrer');
                }
                if ($tag==='li' && in_array($list,['bullet','ordered'],true)) $node->setAttribute('data-list',$list);
                $walk($node);
                if (!in_array($tag,$allowed,true)) { while ($node->firstChild) $parent->insertBefore($node->firstChild,$node); $parent->removeChild($node); }
            } elseif (!($node instanceof DOMText)) $parent->removeChild($node);
        }
    };
    $container=$doc->getElementsByTagName('div')->item(0);
    if (!$container) return '';
    $walk($container);
    $result=''; foreach($container->childNodes as $child) $result.=$doc->saveHTML($child);
    return trim($result);
}
function pretty_date(?string $date): string {
    if (!$date) return 'Без строку';
    $months=['січ','лют','бер','кві','тра','чер','лип','сер','вер','жов','лис','гру'];
    $time=strtotime($date); return date('j',$time).' '.$months[(int)date('n',$time)-1].' '.date('Y',$time);
}
function initials(string $name): string { $parts=preg_split('/\s+/u',trim($name)); return mb_strtoupper(mb_substr($parts[0]??'',0,1).mb_substr($parts[1]??'',0,1)); }
