<?php

namespace App\Controller;

use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\Asset;


class DocumentController extends AbstractController
{
    private $pdf;
    private $logger;

    public function __construct(Pdf $pdf, LoggerInterface $logger = null)
    {
        $this->pdf = $pdf;
        $this->logger = $logger;
    }

    #[Route('api/get-template', name: 'get_template', methods: ["POST"])]
    #[OA\RequestBody(
        request: "json",
        required: true,
        content: new OA\JsonContent(type:"object",example:'{"record_id":"record_id","template_code":"test","external_format":"pdf","data":{"client":"1","client_data":[{"name":"name","phone":"phone"}]}}'),
    )]
    #[OA\Response(
        response: 200,
        description: "successful operation",
        content: new OA\MediaType(mediaType: "application/pdf")
    )]
    #[OA\Response(
        response: 400,
        description: "Bad request"
    )]
    #[OA\Response(
        response: 404,
        description: "Resource Not Found"
    )]
    public function generateDocument(Request $request): Response
    {
        if (!empty($request->getContent())) {
            $template = json_decode($request->getContent(), true);
                $templateCode = $template['template_code'];
                $externalFormat = $template['external_format'];
                $formData = $template['data'];
                $data = array();
                foreach ($formData as $k => $v) {
                    $data[$k] = $v;
                }
                $htmlContent = $this->renderView($templateCode . '.html.twig', $data);
            if ($externalFormat == 'pdf') {
                    $content = $this->pdf->getOutputFromHtml($htmlContent);
            } else {
                $content = $htmlContent;
            }
            return new Response($content, 200, [
                'Content-Type' => 'application/' . $externalFormat,
                'Content-Disposition' => 'attachment; filename="' . $templateCode . '.' . $externalFormat . '"'
            ]);
        } else {
            $respErr = json_encode(array('error' => 'empty request'));
            return new Response($respErr, 201, [
                'Content-Type' => 'application/json',
            ]);
        }
    }
}
