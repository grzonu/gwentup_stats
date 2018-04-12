<?php

namespace App\Controller;

use App\Entity\QueueItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class QueueController extends Controller
{
    /**
     * @Route("/queue/add", name="queue_add")
     */
    public function add(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->headers->has("Nightbot-User")) {
            $header = $request->headers->get("Nightbot-User");
            $parts = [];
            parse_str($header, $parts);
            $name = $parts['name'];
            $provider = $parts['provider'];
            if (empty($name) || empty($provider)) {
                error_log($header);
                return new Response('Brak wymaganych parametrów', 401);
            }
            $dt = new \DateTime();
            $dt->setTime(0, 0, 0);
            $items = $em->createQuery("SELECT p FROM App:QueueItem p WHERE p.created > :today AND p.username = :name")
                ->setParameter('name', $name)
                ->setParameter('today', $dt)
                ->getResult();
            if (count($items) > 0) {
                return new Response("Jesteś już zapisany", 200);
            }
            $item = new QueueItem();
            $item->setPlatform($provider);
            $item->setUsername($name);
            $item->setCreated(new \DateTime());
            $em->persist($item);
            $em->flush();

            return new Response("Zostałeś zapisany na listę", 200);
        }

        return new Response('Niepoprawne zapytanie', 400);
    }

    /**
     * @Route("/queue/clean", name="queue_clean")
     */
    public function clean(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->headers->has("Nightbot-User")) {
            $header = $request->headers->get("Nightbot-User");
            $parts = [];
            parse_str($header, $parts);
            $name = $parts['name'];
            $provider = $parts['provider'];
            $level = $parts['userLevel'];
            if (empty($name) || empty($provider) || empty($level)) {
                error_log($header);
                return new Response('Brak wymaganych parametrów', 401);
            }
            if ($level != "owner") {
                return new Response("Brak uprawnień", 403);
            }
            $dt = new \DateTime();
            $dt->setTime(0, 0, 0);
            $items = $em->createQuery("SELECT p FROM App:QueueItem p WHERE p.created > :today")
                ->setParameter('today', $dt)
                ->getResult();
            foreach ($items as $item) {
                $em->remove($item);
            }
            $em->flush();

            return new Response("Lista wyczyszczona", 200);
        }

        return new Response('Niepoprawne zapytanie', 400);
    }


    /**
     * @Route("/queue/delete", name="queue_delete")
     */
    public function delete(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->headers->has("Nightbot-User")) {
            $header = $request->headers->get("Nightbot-User");
            $parts = [];
            parse_str($header, $parts);
            $name = $parts['name'];
            $provider = $parts['provider'];
            if (empty($name) || empty($provider)) {
                error_log($header);
                return new Response('Brak wymaganych parametrów', 401);
            }
            $dt = new \DateTime();
            $dt->setTime(0, 0, 0);
            $items = $em->createQuery("SELECT p FROM App:QueueItem p WHERE p.created > :today AND p.username = :name")
                ->setParameter('name', $name)
                ->setParameter('today', $dt)
                ->getResult();            
            foreach ($items as $item) {
                $em->remove($item);
            }
            $em->flush();

            return new Response("Zostałeś wypisany z listy", 200);
        }

        return new Response('Niepoprawne zapytanie', 400);
    }

    /**
     * @Route("/queue/list", name="queue_list")
     */
    public function queue_list(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $dt = new \DateTime();
        $dt->setTime(0, 0, 0);
        $items = $em->createQuery("SELECT p FROM App:QueueItem p WHERE p.created > :today ORDER BY p.created ASC")
            ->setParameter('today', $dt)
            ->getResult();
        $i = 1;
        $ret = [];
        foreach($items as $item) {
            $ret[] = $i . '. ' . $item->getUsername();
        }
        return new Response(implode("     ", $ret));
    }
}
