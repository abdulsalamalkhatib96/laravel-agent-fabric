<?php

declare(strict_types=1);

if (! function_exists('mb_strlen')) { function mb_strlen(string $s): int { return strlen($s); } }
if (! function_exists('mb_substr')) { function mb_substr(string $s, int $start, ?int $length = null): string { return $length === null ? substr($s,$start) : substr($s,$start,$length); } }
if (! function_exists('mb_strrpos')) { function mb_strrpos(string $s, string $needle): int|false { return strrpos($s,$needle); } }
if (! function_exists('mb_strtolower')) { function mb_strtolower(string $s): string { return strtolower($s); } }

if (! function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        $config = [
            'agent-fabric.security.redact_keys' => ['password','token','secret'],
            'agent-fabric.security.redact_patterns' => ['/Bearer\s+[A-Za-z0-9._\-]+/i'],
        ];
        return $config[$key] ?? $default;
    }
}

$root = dirname(__DIR__);
$require = static function (string $path) use ($root): void { require_once $root.'/src/'.$path; };

$require('Data/KnowledgeChunk.php');
$require('Contracts/Chunker.php');
$require('Knowledge/SimpleChunker.php');
$require('Runtime/Protocol/AgentEnvelope.php');
$require('Runtime/Protocol/EnvelopeParser.php');
$require('Tools/SchemaValidator.php');
$require('Security/SecretRedactor.php');
$require('Security/FieldPolicy.php');
$require('Security/UrlSafetyGuard.php');
$require('Training/TrainingProfile.php');

use Evolvex\AgentFabric\Knowledge\SimpleChunker;
use Evolvex\AgentFabric\Runtime\Protocol\EnvelopeParser;
use Evolvex\AgentFabric\Security\FieldPolicy;
use Evolvex\AgentFabric\Security\SecretRedactor;
use Evolvex\AgentFabric\Security\UrlSafetyGuard;
use Evolvex\AgentFabric\Tools\SchemaValidator;
use Evolvex\AgentFabric\Training\TrainingProfile;

$assert = static function (bool $condition, string $message): void {
    if (! $condition) throw new RuntimeException($message);
};

$chunks = (new SimpleChunker(120, 20))->chunk(str_repeat('Laravel Agent Fabric safely executes business operations. ', 8));
$assert(count($chunks) > 1, 'Chunker did not split long content.');

$envelope = (new EnvelopeParser)->parse("```json\n{\"type\":\"tool\",\"tool\":\"find_order\",\"arguments\":{\"order_id\":\"42\"}}\n```");
$assert($envelope->tool === 'find_order' && $envelope->arguments['order_id'] === '42', 'Envelope parser failed.');

(new SchemaValidator)->validate([
    'properties' => ['id' => ['type' => 'string'], 'amount' => ['type' => 'number']],
    'required' => ['id','amount'],
], ['id' => 'x', 'amount' => 10.5]);

$redacted = (new SecretRedactor)->redact(['password'=>'abc','nested'=>['token'=>'def'],'header'=>'Bearer secret123']);
$assert($redacted['password'] === '***' && $redacted['nested']['token'] === '***' && $redacted['header'] === '***', 'Secret redaction failed.');

$filtered = (new FieldPolicy(['id','email'], ['email'], ['password']))->filter(['id'=>1,'email'=>'a@example.com','password'=>'x','other'=>'z']);
$assert($filtered === ['id'=>1,'email'=>'[REDACTED]'], 'Field policy failed.');

try { (new UrlSafetyGuard)->assertSafe('http://127.0.0.1/admin'); throw new RuntimeException('SSRF guard allowed private IP.'); }
catch (InvalidArgumentException) {}

$profile = TrainingProfile::make('sales')->role('Sales agent')->goal('Qualify leads')->allowActions(['create_lead'])->never(['change_price'])->languages(['ar','en'])->toArray();
$assert($profile['goal'] === 'Qualify leads' && $profile['forbidden_actions'] === ['change_price'], 'Training profile failed.');

echo "Agent Fabric smoke tests: OK\n";
