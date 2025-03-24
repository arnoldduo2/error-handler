<?php

declare(strict_types=1);

define('EDD_VERSION', egetVersion() ?? 'v0.0.0');
define('EDD_VERSION_NAME', egitCommitHash() ?? 'Initial Release');
