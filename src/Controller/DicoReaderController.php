<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Mot;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\TypeTextType;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use App\Controller\NotEncodableValueException;



class DicoReaderController extends AbstractController
{
    #[Route('/dico/reader', name: 'app_dico_reader')]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {

        $file_path = __DIR__ . '/liste_francais.txt';

        $file_handle = fopen($file_path, 'r');

        if ($file_handle) {
            $file_content  = fread($file_handle, filesize($file_path));

            return $this->json([
                'message' => 'Import words successful'
            ]);
        } else {
            return $this->json([
                'message' => 'file not found'
            ]);
        }
    }

    #[Route('/dico/form', name: 'app_dico_form', methods: ['POST'])]
    public function monFormulaire(Request $request, LoggerInterface $logger)
    {
        try {
            $form = $this->createForm(TypeTextType::class);
            $suggest = [];
            $suggestions = [];

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Traitement des données du formulaire ici

                $formData = $form->getData();
                $userInput = $formData['input_text'];

                $file_path = __DIR__ . '/liste_francais.txt';

                $file_handle = fopen($file_path, 'r');

                if ($file_handle) {
                    $file_content = fread($file_handle, filesize($file_path));
                    $array_userInput = explode(" ", $userInput);
                    $array_file_content = explode("\n", $file_content);

                    $suggest = [];

                    for ($i = 0; $i < count($array_userInput); $i++) {
                        $suggest[$array_userInput[$i]] = []; // Initialize array for each user input word

                        for ($j = 0; $j < count($array_file_content); $j++) {
                            // Check if the first three letters match
                            $filter_1 = strlen($array_userInput[$i]) - 1;
                            $filter_2 = strlen($array_file_content[$j]) - 1;
                            if (strtolower(substr($array_userInput[$i], 0, $filter_1)) === strtolower(substr($array_file_content[$j], 0, $filter_2))) {
                                $suggest[$array_userInput[$i]][] = $array_file_content[$j];
                            }
                        }
                    }

                    fclose($file_handle);

                    // Pass the suggestions and form to the template
                    /*return $this->render('form.html.twig', [
                        'suggestions' => $suggest,
                        'form' => $form->createView(),
                    ]);*/
                    return new JsonResponse(['suggestions' => $suggest]);
                } else {
                    /*return $this->json([
                        'message' => 'file not found'
                    ]);*/
                    return new JsonResponse(['message' => 'Validation failed'], 400);
                }
            }
        } catch (\Exception $e) {
            // Log the exception for further investigation
            $this->$logger->error('An error occurred: ' . $e->getMessage());

            // Return a generic error message
            return new JsonResponse(['message' => 'Internal Server Error'], 500);
        }

        // Pass the form to the template even if there are errors
        /*return $this->render('form.html.twig', [
            'suggestions' => $suggestions,
            'form' => $form->createView(),
        ]);*/
    }

    #[Route('/check_word_existence', name: 'check_word_existence')]
    public function checkWordExistence(Request $request)
    {
        $word = $request->query->get('word');
        $file_path = __DIR__ . '/liste_francais.txt';

        if (!file_exists($file_path)) {
            return $this->json(['error' => 'File not found'], 404);
        }

        $file_content = file_get_contents($file_path);
        $array_file_content = explode("\n", $file_content);

        $wordExists = in_array(mb_strtolower($word), $array_file_content, true);

        return $this->json(['exists' => $wordExists]);
    }

    #[Route('/dico/suggest_lev', name: 'app_dico_lev', methods: ['POST','GET'])]
    public function suggestLevenshtein(Request $request,LoggerInterface $logger)
    {
        $json_receive = $request->getContent();

        try {
            $userInput = json_decode($json_receive, true);

            $file_path = __DIR__ . '/liste_francais.txt';

            $file_handle = fopen($file_path, 'r');

            if ($file_handle) {
                $file_content = fread($file_handle, filesize($file_path));
                $array_userInput = explode(" ", $userInput["input_text"]);
                $array_file_content = explode("\n", $file_content);

                $suggest = [];

                for ($i = 0; $i < count($array_userInput); $i++) {
                    $suggest[$array_userInput[$i]] = []; // Initialize array for each user input word

                    for ($j = 0; $j < count($array_file_content); $j++) {
                        // Check if the first three letters match
                        $filter_1 = strlen($array_userInput[$i]) - 1;
                        $filter_2 = strlen($array_file_content[$j]) - 1;
                        if (strtolower(substr($array_userInput[$i], 0, $filter_1)) === strtolower(substr($array_file_content[$j], 0, $filter_2))) {
                            $suggest[$array_userInput[$i]][] = $array_file_content[$j];
                        }
                    }
                }

                fclose($file_handle);

                // Pass the suggestions and form to the template
                /*return $this->render('form.html.twig', [
                    'suggestions' => $suggest,
                    'form' => $form->createView(),
                ]);*/
                return new JsonResponse(['suggestions' => $suggest]);
            } else {
                /*return $this->json([
                    'message' => 'file not found'
                ]);*/
                return new JsonResponse(['message' => 'Validation failed'], 400);
            }
        } catch (\Exception $e) {
            $this->$logger->error('An error occurred: ' . $e->getMessage());

            // Return a generic error message
            return new JsonResponse(['message' => 'Internal Server Error'], 500);
        }
    }
}
