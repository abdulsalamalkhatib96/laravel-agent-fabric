<?php

return [
    'agents' => [
        'support' => App\Ai\Agents\SupportAgent::class,
    ],
    'routing' => [
        'models' => [
            'primary-reasoner' => [
                'provider' => env('AI_PRIMARY_PROVIDER'),
                'model' => env('AI_PRIMARY_MODEL'),
                'capabilities' => ['reasoning','structured_output','tools'],
                'quality' => .90,
                'tool_accuracy' => .90,
                'reliability' => .90,
                'latency' => .70,
                'cost' => .60,
                'historical_eval' => .80,
            ],
        ],
    ],
];
