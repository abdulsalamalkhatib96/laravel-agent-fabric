<?php

namespace Evolvex\AgentFabric\Enums;

enum ChannelType: string
{
    case Web = 'web';
    case Api = 'api';
    case Websocket = 'websocket';
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Slack = 'slack';
    case Teams = 'teams';
    case Telegram = 'telegram';
    case Sms = 'sms';
    case Voice = 'voice';
    case Webhook = 'webhook';
    case Custom = 'custom';
}
