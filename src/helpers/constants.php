<?php

declare(strict_types=1);

const EDD_VERSION = egitVersion() ?? '1.0.0';
const EDD_VERSION_NAME = egitCommitHash() ?? 'Initial Release';
