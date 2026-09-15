<?php

namespace Evolvex\AgentFabric\Knowledge;

use Evolvex\AgentFabric\Contracts\Embedder;
use Evolvex\AgentFabric\Contracts\Retriever;
use Evolvex\AgentFabric\Data\AgentContext;
use Evolvex\AgentFabric\Data\RetrievalResult;
use Illuminate\Database\ConnectionInterface;

final class DatabaseHybridRetriever implements Retriever
{
    public function __construct(private readonly ConnectionInterface $db, private readonly Embedder $embedder) {}

    public function retrieve(string $query, AgentContext $context, array $sources = [], int $limit = 8): array
    {
        $candidateLimit = (int) config('agent-fabric.knowledge.candidate_limit', 250);
        $q = $this->db->table('ai_knowledge_chunks as c')
            ->join('ai_knowledge_documents as d', 'd.id', '=', 'c.document_id')
            ->where('c.tenant_id', (string) $context->tenantId)
            ->select(['c.id as chunk_id','c.document_id','c.content','c.embedding','c.metadata','d.source_type','d.title']);
        if ($sources !== []) $q->whereIn('d.source_type', $sources);
        $rows = $q->orderByDesc('c.updated_at')->limit($candidateLimit)->get();
        if ($rows->isEmpty()) return [];
        $vector = $this->embedder->embed([$query])[0] ?? [];
        $terms = $this->terms($query); $results = [];
        foreach ($rows as $row) {
            $lexical = $this->lexicalScore($terms, (string) $row->content);
            $stored = $row->embedding ? json_decode((string) $row->embedding, true) : [];
            $semantic = is_array($stored) && $stored !== [] && $vector !== [] ? $this->cosine($vector, $stored) : 0.0;
            $score = max(0.0, min(1.0, ($semantic * .75) + ($lexical * .25)));
            $results[] = new RetrievalResult((string) $row->content, $score, (string) $row->document_id, (string) $row->chunk_id, [
                'source_type' => $row->source_type, 'title' => $row->title,
            ]);
        }
        usort($results, fn ($a, $b) => $b->score <=> $a->score);
        return array_slice($results, 0, $limit);
    }

    private function terms(string $q): array
    {
        preg_match_all('/[\pL\pN]{2,}/u', mb_strtolower($q), $m);
        return array_values(array_unique($m[0] ?? []));
    }
    private function lexicalScore(array $terms, string $text): float
    {
        if ($terms === []) return 0.0; $hay = mb_strtolower($text); $hits = 0;
        foreach ($terms as $t) if (str_contains($hay, $t)) $hits++;
        return $hits / count($terms);
    }
    private function cosine(array $a, array $b): float
    {
        $n = min(count($a), count($b)); if ($n === 0) return 0.0;
        $dot = $aa = $bb = 0.0;
        for ($i=0;$i<$n;$i++) { $x=(float)$a[$i]; $y=(float)$b[$i]; $dot += $x*$y; $aa += $x*$x; $bb += $y*$y; }
        if ($aa <= 0 || $bb <= 0) return 0.0;
        return max(0.0, $dot/(sqrt($aa)*sqrt($bb)));
    }
}
