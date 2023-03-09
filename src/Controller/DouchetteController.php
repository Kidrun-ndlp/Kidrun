<?php

namespace App\Controller;

use PDOException;
use App\Entity\Run;
use App\Entity\Ranking;
use App\Entity\Student;
use App\Repository\RunRepository;
use App\Repository\RankingRepository;
use App\Repository\StudentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DouchetteController extends AbstractController
{
    #[Route('/douchette', name: 'app_douchette')]
    public function createDouchetteAction(Request $request, StudentRepository $studentRepository, RunRepository $runRepository, ManagerRegistry $doctrine, RankingRepository $rankingRepository)
    {
        $error_message = "";
        date_default_timezone_set('Europe/Paris');
        $identifiant = "";
        $form = $this->createFormBuilder()
            ->add('identifiant', TextType::class, [
                'label' => 'Barcode',
                'attr' => [
                    'readonly' => false,
                ],
                'constraints' => [
                    new Length([
                        'min' => 1,
                        'max' => 4,
                        'maxMessage' => 'This value is too long. It should have 4 characters or less',
                    ]),
                    new UpperCase(),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $identifiant = $data['identifiant'];
        }

        $student = $studentRepository->find($identifiant);

        if (isset($student)) {
            $run = $runRepository->getLast(); // Récupération du dernier run
            $existingRanking = $rankingRepository->findOneBy([
                'student' => $student,
                'run' => $run
            ]);
            if ($existingRanking !== null) {
                // L'étudiant a déjà été ajouté à la table tbl_ranking pour cette course
                $error_message .= "Runner already added.";
            } else {
            $end = date("Y-m-d H:i:s");

            $run = $runRepository->getLast();
            $startDateTime = $run->getStart();
            $start = $startDateTime->format("Y-m-d H:i:s");
            $message = "La course a démarré le " . $start . "";

            $ranking = new Ranking();
            $ranking->setStudent($student);
            $ranking->setEnd(new \DateTime($end));
            $ranking->setRun($run);
            $rankingRepository->save($ranking, true);
            }
        } else {
            $error_message .= "Runner not found.";
            $student = new Student();
        }

        return $this->render('douchette/index.html.twig', [
            'form' => $form->createView(),
            'error_message' => $error_message,
        ]);
    }
}

class UpperCase extends Constraint
{
    public $message = 'Veuillez activer votre touche Majuscule "MAJ" sur votre clavier.';

    public function validatedBy()
    {
        return \get_class($this) . 'Validator';
    }
}

class UpperCaseValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!preg_match('/[0-9]/', $value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
