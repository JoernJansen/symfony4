<?php

namespace App\GameBetting\Business\Games;

use App\GameRating\Business\GameRatingFacadeInterface;
use App\GameRating\Persistence\DataProvider\Result;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\GameBetting\Persistence\Entity\UserBetting as UserBettingEntity;
use App\GameCore\Persistence\Entity\Game;
use App\GameBetting\Persistence\DataProvider\GamePastBetting;

class UserPastGames implements UserPastGamesInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var GameRatingFacadeInterface
     */
    private $gameRatingFacade;

    /**
     * @param EntityManagerInterface $entityManager
     * @param GameRatingFacadeInterface $gameRatingFacade
     */
    public function __construct(EntityManagerInterface $entityManager, GameRatingFacadeInterface $gameRatingFacade)
    {
        $this->entityManager = $entityManager;
        $this->gameRatingFacade = $gameRatingFacade;
    }

    /**
     * @param UserInterface $user
     * @return GamePastBetting[]
     */
    public function get(UserInterface $user): array
    {
        $pastGamesForm = [];

        /** @var Game[] $pastGames */
        $pastGames = $this->entityManager
            ->getRepository(Game::class)
            ->findPastGames();

        $gameId2UserBets = $this->getGameIdForUserBets($user, $pastGames);

        foreach ($pastGames as $pastGame) {

            $firstTeamUserResult = null;
            $secondTeamUserResult = null;
            if (isset($gameId2UserBets[$pastGame->getId()])) {
                $firstTeamUserResult = $gameId2UserBets[$pastGame->getId()]->getFirstTeamResult();
                $secondTeamUserResult = $gameId2UserBets[$pastGame->getId()]->getSecondTeamResult();
            }

            $gameResult = new Result(
                (int)$pastGame->getFirstTeamResult(),
                (int)$pastGame->getSecondTeamResult(),
                $firstTeamUserResult,
                $secondTeamUserResult
            );

            $pastGamesForm[$pastGame->getId()] = new GamePastBetting(
                $pastGame->getTeamFirst()->getName(),
                $pastGame->getTeamSecond()->getName(),
                $pastGame->getDate(),
                $gameResult->getFirstTeamResult(),
                $gameResult->getSecondTeamResult(),
                $gameResult->getFirstTeamUserResult(),
                $gameResult->getSecondTeamUserResult(),
                $this->gameRatingFacade->getPoints($gameResult)
            );
        }

        return $pastGamesForm;
    }


    /**
     * @param UserInterface $user
     * @param Game[] $pastGames
     * @return UserBettingEntity[]
     */
    private function getGameIdForUserBets(UserInterface $user, array $pastGames): array
    {
        /** @var UserBettingEntity[] $userBets */
        $userBets = $this->entityManager
            ->getRepository(UserBettingEntity::class)
            ->findUserBettingByUserId($user, $pastGames);

        /** @var UserBettingEntity[] $gameId2UserBets */
        $gameId2UserBets = [];
        foreach ($userBets as $userBet) {
            $gameId2UserBets[$userBet->getGame()->getId()] = $userBet;
        }
        return $gameId2UserBets;
    }
}