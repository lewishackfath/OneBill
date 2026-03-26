<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap/init.php';
require_once APP_PATH . '/repositories/PhoneSystemRepository.php';
require_once APP_PATH . '/repositories/CdrImportRunRepository.php';

$phoneRepo = new PhoneSystemRepository();
$importRepo = new CdrImportRunRepository();
$systems = $phoneRepo->getActivePassiveSocketSystems();

if ($systems === []) {
    fwrite(STDOUT, "No active passive socket phone systems found.\n");
    exit(0);
}

foreach ($systems as $system) {
    $phoneSystemId = (int) $system['id'];
    $clientId = (int) $system['client_id'];
    $host = trim((string) ($system['host'] ?? ''));
    $port = (int) ($system['port'] ?? 0);
    $timeout = max(2, (int) ($system['socket_timeout_seconds'] ?? 10));
    $systemLabel = (string) $system['system_name'];

    $runId = $importRepo->startRun($phoneSystemId, 'socket_session');
    $received = 0;
    $inserted = 0;
    $skipped = 0;
    $status = 'success';
    $errorMessage = null;

    try {
        if ($host === '' || $port <= 0) {
            throw new RuntimeException('Passive socket host/port is not configured.');
        }

        $errno = 0;
        $errstr = '';
        $stream = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!is_resource($stream)) {
            throw new RuntimeException(sprintf('Unable to connect to %s:%d - %s (%d)', $host, $port, $errstr !== '' ? $errstr : 'unknown error', $errno));
        }

        stream_set_timeout($stream, $timeout);
        $importRepo->logListenerEvent($phoneSystemId, $clientId, 'socket_connect', 'info', 'Connected to passive socket.', [
            'host' => $host,
            'port' => $port,
            'timeout_seconds' => $timeout,
            'run_id' => $runId,
        ]);

        while (!feof($stream)) {
            $line = fgets($stream);
            if ($line === false) {
                $meta = stream_get_meta_data($stream);
                if (!empty($meta['timed_out'])) {
                    break;
                }
                continue;
            }

            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $received++;
            $wasInserted = $importRepo->insertRawRecord($phoneSystemId, $clientId, $runId, $line);
            if ($wasInserted) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        fclose($stream);

        $message = sprintf('Passive socket collection completed for %s. Received %d records, inserted %d, skipped %d.', $systemLabel, $received, $inserted, $skipped);
        $phoneRepo->updateLastCollectorStatus($phoneSystemId, true, $message);
        $importRepo->logListenerEvent($phoneSystemId, $clientId, 'socket_collect_complete', 'info', $message, [
            'run_id' => $runId,
            'records_received' => $received,
            'records_inserted' => $inserted,
            'records_skipped' => $skipped,
        ]);
    } catch (Throwable $e) {
        $status = ($received > 0 || $inserted > 0) ? 'partial' : 'failed';
        $errorMessage = $e->getMessage();
        $phoneRepo->updateLastCollectorStatus($phoneSystemId, false, $errorMessage);
        $importRepo->logListenerEvent($phoneSystemId, $clientId, 'socket_collect_error', 'error', $errorMessage, [
            'run_id' => $runId,
            'records_received' => $received,
            'records_inserted' => $inserted,
            'records_skipped' => $skipped,
        ]);
    }

    $importRepo->finishRun($runId, $status, $received, $inserted, $skipped, $errorMessage);
    fwrite(STDOUT, sprintf("[%s] %s -> %s (received=%d inserted=%d skipped=%d)\n", date('Y-m-d H:i:s'), $systemLabel, $status, $received, $inserted, $skipped));
}
