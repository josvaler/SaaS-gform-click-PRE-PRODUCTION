<?php

/**
 * Usage:
 *   php check_div_balance.php admin.php
 *   php check_div_balance.php /ruta/a/otro_archivo.php
 */

if ($argc < 2) {
    fwrite(STDERR, "Uso: php check_div_balance.php <archivo>\n");
    exit(1);
}

$file = $argv[1];

if (!is_readable($file)) {
    fwrite(STDERR, "No se puede leer el archivo: {$file}\n");
    exit(1);
}

$lines = file($file);
$depth = 0;
$issues = [];

foreach ($lines as $num => $line) {
    $lineNumber = $num + 1;
    
    // Buscar <div ...> y </div> en orden
    $matches = [];
    preg_match_all('/<(\/?)div\b[^>]*>/i', $line, $matches, PREG_OFFSET_CAPTURE);
    
    if (!empty($matches[0])) {
        foreach ($matches[1] as $idx => $m) {
            $isClosing = ($m[0] === '/');
            
            if ($isClosing) {
                if ($depth === 0) {
                    // Cierre extra
                    $issues[] = [
                        'line' => $lineNumber,
                        'type' => 'extra_close',
                        'snippet' => trim($line),
                        'message' => "Hay un </div> extra (cierre sin apertura correspondiente). " .
                                     "Revisa este bloque: probablemente sobra este </div> o falta un <div> antes."
                    ];
                } else {
                    $depth--;
                }
            } else {
                $depth++;
            }
        }
    }
}

// Si depth > 0, faltan cierres
if ($depth > 0) {
    $issues[] = [
        'line' => count($lines),
        'type' => 'missing_close',
        'snippet' => '',
        'message' => "El archivo termina con {$depth} <div> sin cerrar. " .
                     "Debes agregar {$depth} </div> al final o cerrar correctamente los bloques donde comenzó el desbalance."
    ];
}

// Salida
echo "Analizando archivo: {$file}\n";
echo "----------------------------------------\n";

if (empty($issues)) {
    echo "✔ No se detectaron problemas de balanceo de <div> / </div>.\n";
    exit(0);
}

// Mostrar issues
foreach ($issues as $issue) {
    echo "Línea {$issue['line']}:\n";
    echo "  Tipo: {$issue['type']}\n";
    if ($issue['snippet'] !== '') {
        echo "  Código: {$issue['snippet']}\n";
    }
    echo "  Sugerencia: {$issue['message']}\n";
    echo "----------------------------------------\n";
}

exit(0);

