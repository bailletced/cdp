<?php

declare(strict_types=1);

namespace App\Chain\Infrastructure\Symfony\Console;

/**
 * F06-T15: demonstration library of 48 public-domain texts, one per slot of
 * an edition — Scripture in the Louis Segond translation (1910), the
 * Carmelite saints, the Fathers and the classic French spiritual writers.
 *
 * Demonstration content only: the texts of a real edition are written by the
 * Communion in the back-office. Bodies stay within what the editor produces
 * (p, br, strong, em, blockquote) and use typographic apostrophes, so the
 * sanitizer keeps them as they are.
 */
final class TextFixtures
{
    /**
     * @return list<array{title: string, source: string, body: string, hints: ?string}>
     */
    public static function all(): array
    {
        return [
            self::text(
                'Que rien ne te trouble',
                'Thérèse d’Avila, Poésies',
                self::verses('Que rien ne te trouble,', 'que rien ne t’effraie,', 'tout passe,', 'Dieu ne change pas.')
                .self::verses('La patience obtient tout ;', 'celui qui possède Dieu', 'ne manque de rien :', '<strong>Dieu seul suffit.</strong>'),
                'Qu’est-ce qui me trouble cette nuit ? Je le dépose devant Dieu.',
            ),
            self::text(
                'L’oraison, commerce d’amitié',
                'Thérèse d’Avila, Livre de la vie, chapitre 8',
                '<p>L’oraison mentale n’est, à mon avis, qu’un commerce intime d’amitié où l’on s’entretient souvent seul à seul avec ce Dieu dont on se sait aimé.</p>',
                'Je me tiens simplement auprès de lui, comme auprès d’un ami.',
            ),
            self::text(
                'Le château intérieur',
                'Thérèse d’Avila, Le Château intérieur, Premières demeures, chapitre 1',
                '<p>Considérons notre âme comme un château fait tout entier d’un seul diamant ou d’un cristal très clair, où il y a beaucoup d’appartements, comme il y a beaucoup de demeures dans le ciel.</p>',
                null,
            ),
            self::text(
                'Je meurs de ne pas mourir',
                'Thérèse d’Avila, Poésies',
                self::verses('Je vis, mais sans vivre en moi-même,', 'et mon espérance est si haute', 'que je meurs de ne pas mourir.'),
                null,
            ),
            self::text(
                'Seulement le regarder',
                'Thérèse d’Avila, Le Chemin de la perfection, chapitre 26',
                '<p>Je ne vous demande pas maintenant de penser à lui, ni de faire de grandes et subtiles considérations. <em>Je ne vous demande rien d’autre que de le regarder.</em></p>',
                'Pendant ces trente minutes, je ne cherche pas à penser : je regarde.',
            ),
            self::text(
                'Nuit obscure',
                'Jean de la Croix, La Nuit obscure, strophe 1',
                self::verses('Par une nuit obscure,', 'brûlant d’amour en mes angoisses,', 'ô l’heureuse fortune !', 'je sortis sans être vue,', 'ma demeure étant déjà en paix.'),
                'Une veille de nuit : l’obscurité n’empêche pas l’amour de se mettre en route.',
            ),
            self::text(
                'Vive flamme d’amour',
                'Jean de la Croix, La Vive Flamme d’amour, strophe 1',
                self::verses('Ô vive flamme d’amour,', 'qui blesses tendrement', 'de mon âme le plus profond centre !'),
                null,
            ),
            self::text(
                'Où t’es-tu caché ?',
                'Jean de la Croix, Le Cantique spirituel, strophe 1',
                self::verses('Où t’es-tu caché,', 'Bien-Aimé, me laissant dans les gémissements ?', 'Comme le cerf tu as fui,', 'après m’avoir blessée ;', 'je sortis criant après toi, et tu étais parti.'),
                null,
            ),
            self::text(
                'Jugés sur l’amour',
                'Jean de la Croix, Dits de lumière et d’amour',
                '<p>Au soir de cette vie, nous serons jugés sur l’amour.</p>',
                'Qui ai-je aimé aujourd’hui ? Qui ai-je oublié d’aimer ?',
            ),
            self::text(
                'Mettez de l’amour',
                'Jean de la Croix, Lettre à la mère María de la Encarnación (1591)',
                '<p>Là où il n’y a pas d’amour, mettez de l’amour, et vous récolterez de l’amour.</p>',
                null,
            ),
            self::text(
                'La prière, un élan du cœur',
                'Thérèse de Lisieux, Manuscrit C',
                '<p>Pour moi, <strong>la prière, c’est un élan du cœur</strong>, c’est un simple regard jeté vers le Ciel, c’est un cri de reconnaissance et d’amour au sein de l’épreuve comme au sein de la joie.</p>',
                'Un simple regard suffit : je n’ai pas besoin de belles phrases.',
            ),
            self::text(
                'Ma vocation, c’est l’Amour',
                'Thérèse de Lisieux, Manuscrit B',
                '<p>Oui, j’ai trouvé ma place dans l’Église, et cette place, ô mon Dieu, c’est vous qui me l’avez donnée… Dans le Cœur de l’Église, ma Mère, <em>je serai l’Amour</em>… ainsi je serai tout.</p>',
                null,
            ),
            self::text(
                'Vivre d’amour',
                'Thérèse de Lisieux, Poésies, « Vivre d’amour »',
                self::verses('Vivre d’Amour, c’est donner sans mesure', 'sans réclamer de salaire ici-bas.', 'Ah ! sans compter je donne, étant bien sûre', 'que lorsqu’on aime, on ne calcule pas !'),
                null,
            ),
            self::text(
                'Rien qu’aujourd’hui',
                'Thérèse de Lisieux, Poésies, « Mon chant d’aujourd’hui »',
                self::verses('Ma vie n’est qu’un instant, une heure passagère,', 'ma vie n’est qu’un seul jour qui m’échappe et qui fuit.', 'Tu le sais, ô mon Dieu ! pour t’aimer sur la terre', 'je n’ai rien qu’aujourd’hui !'),
                'Cette demi-heure est mon « aujourd’hui » : je la donne tout entière.',
            ),
            self::text(
                'L’ascenseur',
                'Thérèse de Lisieux, Manuscrit C',
                '<p>L’ascenseur qui doit m’élever jusqu’au Ciel, ce sont vos bras, ô Jésus ! Pour cela je n’ai pas besoin de grandir, au contraire il faut que je reste petite, que je le devienne de plus en plus.</p>',
                null,
            ),
            self::text(
                'Faire du bien sur la terre',
                'Thérèse de Lisieux, Derniers entretiens, 17 juillet 1897',
                '<p>Je sens que ma mission va commencer, ma mission de faire aimer le bon Dieu comme je l’aime… <strong>Je veux passer mon Ciel à faire du bien sur la terre.</strong></p>',
                'Je confie à Thérèse une personne qui a besoin de ce bien.',
            ),
            self::text(
                'Ô mon Dieu, Trinité que j’adore',
                'Élisabeth de la Trinité, Prière du 21 novembre 1904',
                '<p>Ô mon Dieu, Trinité que j’adore, aidez-moi à m’oublier entièrement pour m’établir en vous, immobile et paisible comme si déjà mon âme était dans l’éternité.</p><p>Que rien ne puisse troubler ma paix ni me faire sortir de vous, ô mon Immuable, mais que chaque minute m’emporte plus loin dans la profondeur de votre Mystère.</p>',
                null,
            ),
            self::text(
                'Mon ciel sur la terre',
                'Élisabeth de la Trinité, Lettres',
                '<p>J’ai trouvé mon ciel sur la terre, puisque le ciel c’est Dieu, et Dieu c’est mon âme.</p>',
                'Dieu habite en moi : je descends le rejoindre là où il est.',
            ),
            self::text(
                'Dans le tracas de ma cuisine',
                'Frère Laurent de la Résurrection, La Pratique de la présence de Dieu',
                '<p>Le temps de l’action n’est pas différent de celui de l’oraison ; je possède Dieu aussi tranquillement dans le tracas de ma cuisine, où quelquefois plusieurs personnes me demandent en même temps des choses différentes, que si j’étais à genoux devant le saint Sacrement.</p>',
                null,
            ),
            self::text(
                'Un oratoire dans le cœur',
                'Frère Laurent de la Résurrection, La Pratique de la présence de Dieu',
                '<p>Il n’est pas nécessaire d’être toujours à l’église pour être avec Dieu ; nous pouvons faire de notre cœur un oratoire, dans lequel nous nous retirions de temps en temps pour nous y entretenir avec lui doucement, humblement et amoureusement.</p>',
                'Où que je sois cette nuit, mon cœur peut devenir un oratoire.',
            ),
            self::text(
                'Fleur du Carmel',
                'Simon Stock, Flos Carmeli',
                self::verses('Fleur du Carmel,', 'vigne fleurie,', 'splendeur du ciel,', 'Vierge féconde, unique !', 'Douce Mère, toujours vierge,', 'aux enfants du Carmel sois propice,', 'Étoile de la mer !'),
                null,
            ),
            self::text(
                'Salve Regina',
                'Antienne mariale, traduction traditionnelle',
                '<p>Salut, ô Reine, Mère de miséricorde, notre vie, notre douceur, notre espérance, salut !</p><p>Enfants d’Ève, exilés, nous crions vers vous ; vers vous nous soupirons, gémissant et pleurant dans cette vallée de larmes.</p><p>Ô vous, notre avocate, tournez vers nous vos regards miséricordieux. Et, après cet exil, montrez-nous Jésus, le fruit béni de votre sein. Ô clémente, ô miséricordieuse, ô douce Vierge Marie !</p>',
                null,
            ),
            self::text(
                'Le Magnificat',
                'Évangile selon saint Luc 1, 46-50 (trad. Louis Segond)',
                self::verses('Mon âme exalte le Seigneur,', 'et mon esprit se réjouit en Dieu, mon Sauveur,', 'parce qu’il a jeté les yeux sur la bassesse de sa servante.', 'Car voici, désormais toutes les générations me diront bienheureuse,', 'parce que le Tout-Puissant a fait pour moi de grandes choses.', 'Son nom est saint,', 'et sa miséricorde s’étend d’âge en âge sur ceux qui le craignent.'),
                'Quelles grandes choses Dieu a-t-il faites pour moi ?',
            ),
            self::text(
                'Le cantique de Siméon',
                'Évangile selon saint Luc 2, 29-32 (trad. Louis Segond)',
                self::verses('Maintenant, Seigneur, tu laisses ton serviteur', 's’en aller en paix, selon ta parole.', 'Car mes yeux ont vu ton salut,', 'salut que tu as préparé devant tous les peuples,', 'lumière pour éclairer les nations,', 'et gloire d’Israël, ton peuple.'),
                null,
            ),
            self::text(
                'Veillez avec moi',
                'Évangile selon saint Matthieu 26, 38-41 (trad. Louis Segond)',
                '<p>Il leur dit alors : Mon âme est triste jusqu’à la mort ; restez ici, et veillez avec moi.</p><blockquote><p>Veillez et priez, afin que vous ne tombiez pas dans la tentation ; l’esprit est bien disposé, mais la chair est faible.</p></blockquote>',
                'Cette nuit, je veille avec le Christ à Gethsémani.',
            ),
            self::text(
                'Venez à moi',
                'Évangile selon saint Matthieu 11, 28-29 (trad. Louis Segond)',
                '<p>Venez à moi, vous tous qui êtes fatigués et chargés, et je vous donnerai du repos. Prenez mon joug sur vous et recevez mes instructions, car je suis doux et humble de cœur ; et vous trouverez du repos pour vos âmes.</p>',
                'Quel fardeau est-ce que je lui remets maintenant ?',
            ),
            self::text(
                'Demeurez en moi',
                'Évangile selon saint Jean 15, 4-5 (trad. Louis Segond)',
                '<p>Demeurez en moi, et je demeurerai en vous.</p><p>Je suis le cep, vous êtes les sarments. Celui qui demeure en moi et en qui je demeure porte beaucoup de fruit, car <em>sans moi vous ne pouvez rien faire</em>.</p>',
                null,
            ),
            self::text(
                'Notre Père',
                'Évangile selon saint Matthieu 6, 9-13 (trad. Louis Segond)',
                self::verses('Notre Père qui es aux cieux !', 'Que ton nom soit sanctifié ;', 'que ton règne vienne ;', 'que ta volonté soit faite sur la terre comme au ciel.', 'Donne-nous aujourd’hui notre pain quotidien ;', 'pardonne-nous nos offenses,', 'comme nous aussi nous pardonnons à ceux qui nous ont offensés ;', 'ne nous induis pas en tentation,', 'mais délivre-nous du malin.'),
                'Je le prie lentement, une demande à la fois.',
            ),
            self::text(
                'Élie au sommet du Carmel',
                'Premier livre des Rois 18, 42-44 (trad. Louis Segond)',
                '<p>Élie monta au sommet du Carmel ; et, se penchant contre terre, il mit son visage entre ses genoux, et dit à son serviteur : Monte, regarde du côté de la mer.</p><p>Le serviteur monta, il regarda, et dit : Il n’y a rien. Élie dit sept fois : Retourne.</p><p>À la septième fois, il dit : <em>Voici un petit nuage qui s’élève de la mer, et qui est comme la paume de la main d’un homme.</em></p>',
                'Persévérer dans la prière, même quand « il n’y a rien ».',
            ),
            self::text(
                'Un murmure doux et léger',
                'Premier livre des Rois 19, 11-12 (trad. Louis Segond)',
                '<p>Et voici, l’Éternel passa. Et devant l’Éternel, il y eut un vent fort et violent qui déchirait les montagnes et brisait les rochers : l’Éternel n’était pas dans le vent.</p><p>Et après le vent, ce fut un tremblement de terre : l’Éternel n’était pas dans le tremblement de terre. Et après le tremblement de terre, un feu : l’Éternel n’était pas dans le feu.</p><p>Et après le feu, <strong>un murmure doux et léger</strong>.</p>',
                'Je fais silence pour entendre ce murmure.',
            ),
            self::text(
                'Viens, mon amie',
                'Cantique des cantiques 2, 10-12 (trad. Louis Segond)',
                self::verses('Lève-toi, mon amie, ma belle, et viens !', 'Car voici, l’hiver est passé ;', 'la pluie a cessé, elle s’en est allée.', 'Les fleurs paraissent sur la terre,', 'le temps de chanter est arrivé.'),
                null,
            ),
            self::text(
                'Je t’appelle par ton nom',
                'Livre d’Ésaïe 43, 1 (trad. Louis Segond)',
                '<p>Ainsi parle maintenant l’Éternel, qui t’a créé, ô Jacob ! Celui qui t’a formé, ô Israël !</p><blockquote><p>Ne crains rien, car je te rachète, je t’appelle par ton nom : tu es à moi !</p></blockquote>',
                'Dieu prononce mon nom : je l’écoute.',
            ),
            self::text(
                'L’Éternel est mon berger',
                'Psaume 23, 1-4 (trad. Louis Segond)',
                self::verses('L’Éternel est mon berger :', 'je ne manquerai de rien.', 'Il me fait reposer dans de verts pâturages,', 'il me dirige près des eaux paisibles.', 'Il restaure mon âme,', 'il me conduit dans les sentiers de la justice,', 'à cause de son nom.')
                .self::verses('Quand je marche dans la vallée de l’ombre de la mort,', 'je ne crains aucun mal, car tu es avec moi.'),
                null,
            ),
            self::text(
                'Comme une biche',
                'Psaume 42, 2-3 (trad. Louis Segond)',
                self::verses('Comme une biche soupire après des courants d’eau,', 'ainsi mon âme soupire après toi, ô Dieu !', 'Mon âme a soif de Dieu, du Dieu vivant :', 'quand irai-je et paraîtrai-je devant la face de Dieu ?'),
                'De quoi mon âme a-t-elle soif ?',
            ),
            self::text(
                'Mon âme a soif de toi',
                'Psaume 63, 2 (trad. Louis Segond)',
                self::verses('Ô Dieu ! tu es mon Dieu, je te cherche ;', 'mon âme a soif de toi, mon corps soupire après toi,', 'dans une terre aride, desséchée, sans eau.'),
                null,
            ),
            self::text(
                'Du fond de l’abîme',
                'Psaume 130, 1-6 (trad. Louis Segond)',
                self::verses('Du fond de l’abîme je t’invoque, ô Éternel !', 'Seigneur, écoute ma voix !', 'Que tes oreilles soient attentives', 'à la voix de mes supplications !')
                .self::verses('Mon âme compte sur le Seigneur,', 'plus que les gardes ne comptent sur le matin,', 'que les gardes ne comptent sur le matin.'),
                'Comme les gardes, j’attends le matin : je veille dans l’espérance.',
            ),
            self::text(
                'D’où me viendra le secours ?',
                'Psaume 121, 1-4 (trad. Louis Segond)',
                self::verses('Je lève mes yeux vers les montagnes…', 'D’où me viendra le secours ?', 'Le secours me vient de l’Éternel,', 'qui a fait les cieux et la terre.')
                .self::verses('Voici, il ne sommeille ni ne dort,', 'celui qui garde Israël.'),
                null,
            ),
            self::text(
                'Comme un enfant sevré',
                'Psaume 131 (trad. Louis Segond)',
                self::verses('Éternel ! je n’ai ni un cœur qui s’enfle, ni des regards hautains ;', 'je ne m’occupe pas de choses trop grandes et trop relevées pour moi.', 'Loin de là, j’ai l’âme calme et tranquille,', 'comme un enfant sevré qui est auprès de sa mère ;', 'j’ai l’âme comme un enfant sevré.'),
                null,
            ),
            self::text(
                'Ma lumière et mon salut',
                'Psaume 27, 1.4 (trad. Louis Segond)',
                self::verses('L’Éternel est ma lumière et mon salut :', 'de qui aurais-je crainte ?', 'L’Éternel est le soutien de ma vie :', 'de qui aurais-je peur ?')
                .self::verses('Je demande à l’Éternel une chose, que je désire ardemment :', 'je voudrais habiter toute ma vie dans la maison de l’Éternel,', 'pour contempler la magnificence de l’Éternel.'),
                null,
            ),
            self::text(
                'Bénissez l’Éternel pendant les nuits',
                'Psaume 134 (trad. Louis Segond)',
                self::verses('Voici, bénissez l’Éternel, vous tous, serviteurs de l’Éternel,', 'qui vous tenez dans la maison de l’Éternel pendant les nuits !', 'Élevez vos mains vers le sanctuaire,', 'et bénissez l’Éternel !'),
                'La prière de ceux qui veillent : je rejoins tous ceux qui prient cette nuit.',
            ),
            self::text(
                'Je me couche en paix',
                'Psaume 4, 7-9 (trad. Louis Segond)',
                self::verses('Fais lever sur nous la lumière de ta face, ô Éternel !', 'Tu mets dans mon cœur plus de joie qu’ils n’en ont', 'quand abondent leur froment et leur moût.', 'Je me couche et je m’endors en paix,', 'car toi seul, ô Éternel ! tu me donnes la sécurité dans ma demeure.'),
                null,
            ),
            self::text(
                'À l’ombre du Tout-Puissant',
                'Psaume 91, 1-2.5 (trad. Louis Segond)',
                self::verses('Celui qui demeure sous l’abri du Très-Haut', 'repose à l’ombre du Tout-Puissant.', 'Je dis à l’Éternel : Mon refuge et ma forteresse,', 'mon Dieu en qui je me confie !')
                .self::verses('Tu ne craindras ni les terreurs de la nuit,', 'ni la flèche qui vole de jour.'),
                null,
            ),
            self::text(
                'Seigneur, tu me sondes',
                'Psaume 139, 1-3.11-12 (trad. Louis Segond)',
                self::verses('Éternel ! tu me sondes et tu me connais,', 'tu sais quand je m’assieds et quand je me lève,', 'tu pénètres de loin ma pensée ;', 'tu sais quand je marche et quand je me couche.')
                .self::verses('Si je dis : Au moins les ténèbres me couvriront,', 'la nuit devient lumière autour de moi ;', 'même les ténèbres ne sont pas obscures pour toi,', 'la nuit brille comme le jour.'),
                'Rien de moi ne lui est caché : je me laisse connaître.',
            ),
            self::text(
                'Notre cœur est sans repos',
                'Augustin d’Hippone, Les Confessions, livre I, 1',
                '<p>Tu nous as faits pour toi, Seigneur, et notre cœur est sans repos tant qu’il ne repose pas en toi.</p>',
                null,
            ),
            self::text(
                'Bien tard je t’ai aimée',
                'Augustin d’Hippone, Les Confessions, livre X, 27',
                '<p>Bien tard je t’ai aimée, ô beauté si ancienne et si nouvelle, bien tard je t’ai aimée ! Et voici que tu étais au-dedans, et moi au-dehors, et c’est là que je te cherchais.</p><p>Tu étais avec moi, et je n’étais pas avec toi.</p>',
                'Je cesse de le chercher au-dehors : il est au-dedans.',
            ),
            self::text(
                'L’amour est une grande chose',
                'L’Imitation de Jésus-Christ, livre III, chapitre 5',
                '<p>L’amour est une grande chose, un bien au-dessus de tous les biens : seul il rend léger ce qui est pesant, et fait qu’on supporte avec une âme égale toutes les vicissitudes de la vie.</p>',
                null,
            ),
            self::text(
                'Il ne faut pas dormir',
                'Blaise Pascal, Pensées, « Le Mystère de Jésus »',
                '<p>Jésus sera en agonie jusqu’à la fin du monde : <strong>il ne faut pas dormir pendant ce temps-là.</strong></p><blockquote><p>Console-toi, tu ne me chercherais pas si tu ne m’avais trouvé.</p></blockquote>',
                'Veiller, c’est tenir compagnie au Christ qui veille.',
            ),
            self::text(
                'Prière d’abandon',
                'Charles de Foucauld, Méditations',
                '<p>Mon Père, je m’abandonne à toi, fais de moi ce qu’il te plaira. Quoi que tu fasses de moi, je te remercie.</p><p>Je suis prêt à tout, j’accepte tout. Pourvu que ta volonté se fasse en moi, en toutes tes créatures, je ne désire rien d’autre, mon Dieu.</p>',
                null,
            ),
        ];
    }

    /**
     * Psalm-like stanza: one paragraph, one line per verse (Shift + Entrée in
     * the editor).
     */
    private static function verses(string ...$lines): string
    {
        return '<p>'.implode('<br />', $lines).'</p>';
    }

    /**
     * @return array{title: string, source: string, body: string, hints: ?string}
     */
    private static function text(string $title, string $source, string $body, ?string $hints): array
    {
        return ['title' => $title, 'source' => $source, 'body' => $body, 'hints' => $hints];
    }
}
