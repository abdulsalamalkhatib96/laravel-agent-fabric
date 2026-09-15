<?php

namespace Evolvex\AgentFabric\Enums;

enum ModelCapability: string
{
    case Text = 'text';
    case Reasoning = 'reasoning';
    case Tools = 'tools';
    case StructuredOutput = 'structured_output';
    case Vision = 'vision';
    case AudioInput = 'audio_input';
    case AudioOutput = 'audio_output';
    case Video = 'video';
    case Files = 'files';
    case Embeddings = 'embeddings';
    case Reranking = 'reranking';
    case FileSearch = 'file_search';
    case VectorStore = 'vector_store';
    case WebSearch = 'web_search';
    case LongContext = 'long_context';
}
