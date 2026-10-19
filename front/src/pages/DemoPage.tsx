import {
  Button,
  Checkbox,
  Description,
  FieldError,
  Input,
  Label,
  Link,
  Spinner,
  TextField,
} from "@heroui/react";

/**
 * /demo (F01-T05): demonstration page of the themed HeroUI components.
 * Shows the carmelite theme tokens, the 18 px body text and the variants
 * used by the application screens.
 */
export function DemoPage() {
  return (
    <main className="mx-auto grid w-full max-w-3xl gap-8 px-4 py-8">
      <div className="grid gap-2">
        <h1>Composants</h1>
        <p>
          Démonstration du thème carmélitain (F01) : boutons, champs, case à
          cocher, lien et chargement, corps de texte 18 px.
        </p>
      </div>

      <section aria-labelledby="demo-buttons" className="grid gap-4">
        <h2 id="demo-buttons">Boutons</h2>
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="primary">Principal</Button>
          <Button variant="secondary">Secondaire</Button>
          <Button variant="outline">Contour</Button>
          <Button variant="ghost">Fantôme</Button>
          <Button variant="danger">Danger</Button>
        </div>
        <div className="flex flex-wrap items-center gap-4">
          <Button variant="primary" size="sm">
            Petit
          </Button>
          <Button variant="primary" size="lg">
            Grand
          </Button>
          <Button variant="primary" isDisabled>
            Désactivé
          </Button>
          <Button variant="primary" isPending>
            En cours
          </Button>
        </div>
      </section>

      <section aria-labelledby="demo-fields" className="grid gap-4">
        <h2 id="demo-fields">Champs de formulaire</h2>
        <TextField className="max-w-md">
          <Label>Adresse e-mail</Label>
          <Input type="email" placeholder="marie@exemple.fr" />
          <Description>Un lien de connexion vous sera envoyé.</Description>
          <FieldError />
        </TextField>
        <TextField className="max-w-md" isInvalid>
          <Label>Nom</Label>
          <Input placeholder="Dupont" />
          <FieldError>Le nom est obligatoire.</FieldError>
        </TextField>
      </section>

      <section aria-labelledby="demo-checkbox" className="grid gap-4">
        <h2 id="demo-checkbox">Case à cocher</h2>
        <Checkbox>
          <Checkbox.Content>
            <Checkbox.Control>
              <Checkbox.Indicator />
            </Checkbox.Control>
            J&apos;accepte la politique de confidentialité
          </Checkbox.Content>
        </Checkbox>
      </section>

      <section aria-labelledby="demo-link" className="grid gap-4">
        <h2 id="demo-link">Lien et chargement</h2>
        <p>
          <Link href="/">Retour à l&apos;accueil</Link>
        </p>
        <Spinner aria-label="Chargement en cours" />
      </section>
    </main>
  );
}
