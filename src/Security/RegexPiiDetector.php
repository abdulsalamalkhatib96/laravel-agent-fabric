<?php

namespace Evolvex\AgentFabric\Security;

use Evolvex\AgentFabric\Contracts\PiiDetector;

final class RegexPiiDetector implements PiiDetector
{
    private array $patterns=[
        'email'=>'/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
        'phone'=>'/(?<!\d)(?:\+?971|0)?5\d{8}(?!\d)/',
        'card'=>'/(?<!\d)(?:\d[ -]*?){13,19}(?!\d)/',
        'bearer'=>'/Bearer\s+[A-Za-z0-9._\-]+/i',
        'api_key'=>'/(?:sk|pk|api)[-_][A-Za-z0-9_-]{16,}/i',
    ];
    public function detect(string $text):array{$out=[];foreach($this->patterns as $type=>$pattern){if(preg_match_all($pattern,$text,$m))foreach($m[0] as $value)$out[]=['type'=>$type,'value'=>$value];}return $out;}
    public function redact(string $text,array $types=[]):string{foreach($this->patterns as $type=>$pattern){if($types!==[]&&!in_array($type,$types,true))continue;$text=(string)preg_replace($pattern,'[REDACTED:'.strtoupper($type).']',$text);}return $text;}
}
