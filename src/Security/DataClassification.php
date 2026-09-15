<?php

namespace Evolvex\AgentFabric\Security;

enum DataClassification: string { case Public='public';case Internal='internal';case Confidential='confidential';case Sensitive='sensitive';case Secret='secret';case Forbidden='forbidden'; }
