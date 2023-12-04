<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Mot;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\TypeTextType;
use Symfony\Component\HttpFoundation\Request;


class DicoReaderController extends AbstractController
{
    #[Route('/dico/reader', name: 'app_dico_reader')]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {

        $file_path = __DIR__.'/liste_francais.txt';

        $file_handle = fopen($file_path,'r');

        if ($file_handle) {
            $file_content  = fread($file_handle,filesize($file_path));        
            
            return $this->json([
                'message' => 'Import words successful'
            ]);


        } else {
            return $this->json([
            'message' => 'file not found'
        ]);
        }    
    }

    #[Route('/dico/form', name: 'app_dico_form')]
    public function monFormulaire(Request $request)
    {
        $form = $this->createForm(TypeTextType::class);
        $suggest = [];
        $suggestions = [];

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement des données du formulaire ici

            $formData = $form->getData();
            $userInput = $formData['input_text'];

            $file_path = __DIR__.'/liste_francais.txt';

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
                        $filter_1 = strlen($array_userInput[$i])-1;
                        $filter_2 = strlen($array_file_content[$j])-1;
                        if (strtolower(substr($array_userInput[$i], 0, $filter_1)) === strtolower(substr($array_file_content[$j], 0, $filter_2))) {
                            $suggest[$array_userInput[$i]][] = $array_file_content[$j];
                        }
                    }
                }

                fclose($file_handle);

                // Pass the suggestions and form to the template
                return $this->render('form.html.twig', [
                    'suggestions' => $suggest,
                    'form' => $form->createView(),
                ]);
            } else {
                return $this->json([
                    'message' => 'file not found'
                ]);
            }
        }

        // Pass the form to the template even if there are errors
        return $this->render('form.html.twig', [
            'suggestions' => $suggestions,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/check_word_existence', name: 'check_word_existence')]
    public function checkWordExistence(Request $request)
    {
        $word = $request->query->get('word');
        $file_path = __DIR__.'/liste_francais.txt';

        if (!file_exists($file_path)) {
            return $this->json(['error' => 'File not found'], 404);
        }

        $file_content = file_get_contents($file_path);
        $array_file_content = explode("\n", $file_content);

        $wordExists = in_array(mb_strtolower($word), $array_file_content, true);

        return $this->json(['exists' => $wordExists]);
    }
}
