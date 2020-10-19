<?php

namespace ePayco\Kernel\Exception;


use Symfony\Component\HttpFoundation\Response;

/**
 * Visualiza el detalle del error en un formato html
 **/
class HtmlHandler extends AbstractExceptionHandler
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
        $this->html .= "<title>ePayco - Ha ocurrido un evento imprevisto</title>";

        $this->html .= <<<EOT
        <style>
            body { font-family: Helvetica, Arial, sans-serif;color:#333; text-align:center;}
            p{padding:10px;}
        </style>
EOT;
        $this->html .= "</head><body>";

        $this->html .= <<<SVG
<svg xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" id="svg2" width="300px" viewBox="0 0 32 32" version="1.1" height="300px">
  <g id="g4283">
    <g fill="none" fill-rule="evenodd" id="Page-1" stroke="none" stroke-width="1">
      <g fill="#157EFB" id="icon-131-cloud-error">
        <path
           d="M 8.0028165,24 C 5.7979383,24 4,22.209139 4,20 4,18.10461 5.3246099,16.511736 7.1010092,16.102154 l 0,0 C 7.0346763,15.744882 7,15.37649 7,15 7,11.686291 9.6862913,9 13,9 c 2.615442,0 4.840026,1.673457 5.661424,4.008041 C 19.435776,12.377812 20.423767,12 21.5,12 c 2.358343,0 4.292964,1.814166 4.484438,4.123072 l 0,0 C 27.714492,16.563097 29,18.132016 29,20 c 0,2.204644 -1.792122,4 -4.002817,4 -5.664789,0 -11.329578,0 -16.9943665,0 z M 25.00056,25 C 27.761675,25 30,22.755805 30,20 30,17.903581 28.713291,16.108508 26.882863,15.36551 l 0,0 C 26.360022,12.872249 24.148655,11 21.5,11 20.637102,11 19.820616,11.198716 19.093808,11.552882 17.891182,9.4314488 15.612757,8 13,8 9.1340066,8 6,11.134007 6,15 c 0,0.138151 0.004,0.275367 0.011897,0.411539 l 0,0 C 4.2396588,16.181608 3,17.949131 3,20 3,22.761424 5.2324942,25 7.9994399,25 13.66648,25 19.33352,25 25.00056,25 Z"
           id="cloud-error" />
      </g>
    </g>
    <g id="g4274" transform="matrix(1.2463435,0,0,1.2463435,135.82895,-9.5357971)" style="stroke:#157efb;stroke-opacity:1;fill:#000000;fill-opacity:0">
      <g id="g4260" style="stroke:#157efb;stroke-opacity:1;fill:#000000;fill-opacity:0">
        <path
           style="fill:#000000;fill-rule:evenodd;stroke:#157efb;stroke-width:0.5;stroke-linecap:round;stroke-linejoin:miter;stroke-miterlimit:4;stroke-dasharray:none;stroke-opacity:1;fill-opacity:0"
           d="m -92.714882,20.302776 -1.390244,1.294365 1.390244,1.198486"
           id="path3456" />
        <path
           style="fill:#000000;fill-rule:evenodd;stroke:#157efb;stroke-width:0.5;stroke-linecap:round;stroke-linejoin:miter;stroke-miterlimit:4;stroke-dasharray:none;stroke-opacity:1;fill-opacity:0"
           d="m -93.949323,21.597141 1.773759,0"
           id="path4258" />
      </g>
      <path
         id="path4266"
         d="m -97.988221,25.851766 c 1.03819,-2.140591 3.619533,-2.020979 4.554247,0"
         style="fill:#000000;fill-rule:evenodd;stroke:#157efb;stroke-width:0.5;stroke-linecap:round;stroke-linejoin:miter;stroke-miterlimit:4;stroke-dasharray:none;stroke-opacity:1;fill-opacity:0" />
      <g transform="matrix(-1,0,0,-1,-191.48645,43.19576)" id="g4268" style="stroke:#157efb;stroke-opacity:1;fill:#000000;fill-opacity:0">
        <path
           id="path4270"
           d="m -92.714882,20.302776 -1.390244,1.294365 1.390244,1.198486"
           style="fill:#000000;fill-rule:evenodd;stroke:#157efb;stroke-width:0.5;stroke-linecap:round;stroke-linejoin:miter;stroke-miterlimit:4;stroke-dasharray:none;stroke-opacity:1;fill-opacity:0" />
        <path
           id="path4272"
           d="m -93.949323,21.597141 1.773759,0"
           style="fill:#000000;fill-rule:evenodd;stroke:#157efb;stroke-width:0.5;stroke-linecap:round;stroke-linejoin:miter;stroke-miterlimit:4;stroke-dasharray:none;stroke-opacity:1;fill-opacity:0" />
      </g>
    </g>
  </g>
</svg>
SVG;

        $this->html .= "<p>Se presentó un evento inespereado con la aplicación, el servidor devolvió el siguiete
            mensaje:<br> <h3>{$this->mensaje}</h3> <br>Si se sigue presentando por favor comunicarse con el área de desarrollo.</p>";

        $this->html .= '<div style="display:none;text-align:left;color:#8b1014;"><b>Evento ocurrió en el archivo <code>' . $this->archivo .
            '</code> en la línea ' . $this->linea . '</b><pre>' . $this->backtrace . '</pre></div>';

        $this->html .= <<<SVG
<svg xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" id="svg2" viewBox="0 0 976.06342 184.4968" width="400px" version="1.1">
  <g id="layer1" transform="matrix(0.99710894,0,0,0.99710894,-5.3688566,-548.95591)">
    <g style="fill:#8dc53c;fill-opacity:1;stroke:none" id="text2985" transform="matrix(0.96063238,0,0,0.96063238,-81.577666,46.997336)">
      <path style="fill:#8dc53c;fill-opacity:1" id="path3003"
         d="m 382.99378,666.27169 103.92735,0 0,50.25059 -155.59109,0 0,-190.44958 137.69883,0.10645 0,51.39265 -86.03509,0 0,19.415 63.19392,0 0,49.86989 -63.19392,0 0,19.415 0,0" />
    </g>
    <path style="fill:#8dc53c;fill-opacity:1;stroke:none" id="path3001-9"
       d="m 982.86289,712.50536 -39.86128,21.57628 -56.31765,-100.56738 -55.95204,100.56738 -39.86125,-21.57628 95.5838,-161.37575 96.40842,161.37575 0,0" />
    <path style="fill:#8dc53c;fill-opacity:1;stroke:none" id="path3001"
       d="M 198.79167,712.50537 158.93042,734.08165 102.61268,633.51427 46.660696,734.08165 6.7994468,712.50537 102.38318,551.12961 l 96.40849,161.37576 0,0" />
    <path style="fill:#8dc53c;fill-opacity:1;stroke:none" id="path3005"
       d="m 588.61703,707.70015 c -8.53311,8.0454 -18.28506,14.50608 -29.25597,19.38207 -10.97109,4.6322 -22.79532,6.94829 -35.47285,6.94829 -12.67765,0 -24.62383,-2.31609 -35.83855,-6.94829 -11.21483,-4.87599 -20.96681,-11.45857 -29.25596,-19.74778 -8.28921,-8.28915 -14.87179,-17.91923 -19.74778,-28.89026 -4.6322,-11.21472 -6.94829,-23.03899 -6.94829,-35.47286 0,-12.43367 2.31609,-24.13605 6.94829,-35.10715 4.87599,-11.21464 11.45857,-20.96663 19.74778,-29.25596 8.28915,-8.53283 18.04113,-15.23731 29.25596,-20.11347 11.21472,-4.87582 23.1609,-7.31382 35.83855,-7.314 12.67753,1.8e-4 24.50176,2.43818 35.47285,7.314 10.97091,4.63236 20.72286,11.21495 29.25597,19.74777 l -35.10718,35.47285 c -8.28929,-8.28905 -18.16315,-12.43365 -29.62164,-12.43378 -5.85127,1.3e-4 -11.45866,1.09723 -16.82218,3.2913 -5.11986,1.95053 -9.63014,4.87611 -13.53088,8.77679 -3.90085,3.65711 -7.07024,8.0455 -9.50818,13.16518 -2.19425,5.11989 -3.29135,10.60538 -3.2913,16.45647 -5e-5,6.09508 1.09705,11.70247 3.2913,16.82219 2.43794,5.11986 5.60733,9.63014 9.50818,13.53088 3.90074,3.65705 8.41102,6.58264 13.53088,8.77679 5.36352,2.19424 10.97091,3.29134 16.82218,3.2913 5.8511,4e-5 11.21469,-1.09706 16.09075,-3.2913 4.87588,-2.19415 9.38624,-5.24164 13.53089,-9.1425 l 35.10718,34.74147" />
    <path style="fill:#8dc53c;fill-opacity:1;stroke:none" id="path3007"
       d="m 764.84537,678.07849 c -9e-5,7.80163 -1.46294,15.11562 -4.38836,21.94197 -2.9257,6.58262 -6.94844,12.43381 -12.06813,17.55358 -4.87608,5.1198 -10.72729,9.1425 -17.55354,12.06808 -6.82645,2.9256 -14.14051,4.38839 -21.942,4.38839 l 0,0.36571 -12.79947,0 0,-0.36571 c -7.80168,0 -15.11564,-1.46279 -21.94199,-4.38839 -6.82645,-2.92558 -12.79947,-6.94828 -17.91925,-12.06808 -4.87598,-5.11977 -8.77682,-10.97096 -11.70243,-17.55358 -6.32192,-13.61905 -2.7427,-24.75768 -2.7427,-24.75768 l 45.89882,0.99566 c 0.60472,3.0427 1.09349,5.47709 2.55634,6.93985 1.46276,1.46284 3.41313,2.19423 5.85121,2.19419 l 12.79947,0 c 1.95037,4e-5 3.77884,-0.73135 5.4855,-2.19419 1.70656,-1.46276 2.5598,-3.16935 2.55989,-5.1198 l 0,-4.75409 c -9e-5,-1.95035 -0.85333,-3.65694 -2.55989,-5.1198 -1.70666,-1.46273 -3.53513,-2.19413 -5.4855,-2.1942 l -12.79947,0 c -7.55787,7e-5 -14.74993,-1.34083 -21.57628,-4.02269 -6.58264,-2.92552 -12.43385,-6.82632 -17.55354,-11.70239 -5.11988,-5.1197 -9.26443,-10.97089 -12.43385,-17.55358 -2.9256,-6.58248 -4.38836,-13.53076 -4.38836,-20.84486 l 0,-5.4855 c 0,-7.55765 1.46276,-14.74974 4.38836,-21.57628 2.92561,-6.82623 6.82645,-12.67742 11.70243,-17.55358 5.11978,-5.11961 11.0928,-9.14231 17.91925,-12.06807 6.82635,-2.92542 14.14031,-4.38822 21.94199,-4.3884 33.59793,0.14033 48.09541,6.20524 59.0789,15.97286 -3.22975,8.77015 -27.22336,37.17667 -27.22336,37.17667 -5.78503,-5.03091 -9.90364,-6.13809 -15.64525,-7.66885 -5.11652,-1.31719 -10.70615,-1.73642 -16.45294,0.72779 -2.43799,1.3e-4 -4.1458,2.5516 -5.60856,4.25807 -1.46285,1.46293 -2.19427,3.16952 -2.19418,5.11979 l 0,4.3884 c 0.48752,1.95051 1.46276,3.53521 2.92561,4.75409 1.46275,0.97531 3.16932,1.70672 5.11978,2.1942 l 12.79947,0 c 7.80149,1.1e-4 15.11555,1.46291 21.942,4.38839 6.82625,2.92571 12.67746,6.9484 17.55354,12.06809 5.11969,4.87608 9.14243,10.72727 12.06813,17.55357 2.92542,6.82647 4.38827,14.01855 4.38836,21.57628 l 0,4.75409" />
  </g>
</svg>
SVG;
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

