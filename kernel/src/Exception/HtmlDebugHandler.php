<?php
namespace ePayco\Kernel\Exception;


use Symfony\Component\HttpFoundation\Response;

/**
 * Visualiza el detalle del error en un formato html, util para desarrolladores
 **/
class HtmlDebugHandler extends AbstractExceptionHandler
{
    /**
     * @var string Contenido de la pagina html
     */
    private $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';

    /**
     * Procesa el error para mostrarlo al usuario
     */
    public function handle()
    {
        $this->html .= "<title>{$this->clase} - {$this->codigo}</title>";

        $this->html .= <<<EOT
        <style>
            body {
                font-family: Helvetica, Arial, sans-serif;color:#333;
            }
            h1{color: #8b1014;}
            p{padding:10px;}
            pre {word-wrap: break-word;overflow:auto;margin:10px;background-color: #fefefe;margin: 10px;border: 1px solid #cacaca; padding:2px;}
            pre .number{background-color: #eee; display: inline-block; padding: 0 7px 0 0; text-align: right; width: 30px;}
        </style>
EOT;
        $this->html .= "</head><body>";

        $this->html .= "<h1>{$this->mensaje}</h1><p><code>{$this->archivo}</code> en la línea {$this->linea}</p>";
        $this->html .= '<pre>' . $this->getFragmentCode($this->archivo, $this->linea) . '</pre>';
        $this->html .= "<h2>Stacktrace</h2><pre>{$this->backtrace}</pre>";

        $this->html .= "</body></html>";

        $response = new Response($this->html, $this->codigoHttp);

        $response->send();
    }

    /**
     * Muestra la linea donde se presenta el error
     * @param string $file Contiene la url del archivo
     * @param integer $line Contiene la linea del error
     * @return string
     */
    private function getFragmentCode(string $file, int $line): string
    {
        $arrFile = file($file);
        $lineStart = $line - 6;
        $lineStart = ($lineStart < 0 ? 0 : $lineStart);
        $lineCounter = $lineStart + 1;
        $code =  array_reduce(array_slice($arrFile, $lineStart, 10), function($res, $item) use(&$lineCounter, $line) {
            $res .= sprintf('<span class="number" %s>%s</span>%s%s%s',
                ($lineCounter == $line ? 'style="font-weight:bold;color:#8b1014;"' : ''),
                $lineCounter,
                ($lineCounter == $line ? '<strong style="color:#8b1014;">' : ''),
                $item,
                ($lineCounter == $line ? '</strong>' : '')
            );
            $lineCounter++;
            return $res;
        });

        return $code;
    }
}

