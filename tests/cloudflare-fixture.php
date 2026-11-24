<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers/cloudflare-fixture.functions.php';


require_once __DIR__ . '/../plugins/jlmsvideo/cloudflarestream/src/Provider/Provider.php';

/** No live credentials, no outbound HTTP. Test transport is injected only in disposable fixtures. */
