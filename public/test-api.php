<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Markdown di test
$markdownResponse = <<<MARKDOWN
# Titolo Principale

Questo è un **testo in grassetto** e questo è in *corsivo*.

## Sottotitolo

Ecco una lista:
- Primo elemento
- Secondo elemento
- Terzo elemento con **grassetto**

### Codice

Ecco un esempio di codice:

\`\`\`javascript
function hello() {
    console.log("Hello World!");
}
\`\`\`

### Link e altro

Visita [questo link](https://example.com) per maggiori informazioni.

> Questa è una citazione importante

### Tabella

| Colonna 1 | Colonna 2 | Colonna 3 |
|-----------|-----------|-----------|
| Dato 1    | Dato 2    | Dato 3    |
| Dato 4    | Dato 5    | Dato 6    |

MARKDOWN;

$response = [
    'success' => true,
    'response' => $markdownResponse
];

echo json_encode($response);
?>
