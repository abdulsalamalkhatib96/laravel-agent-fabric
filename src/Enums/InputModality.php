<?php

namespace Evolvex\AgentFabric\Enums;

enum InputModality: string
{
    case Text = 'text';
    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';
    case File = 'file';
}
