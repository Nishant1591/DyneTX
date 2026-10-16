<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* @dynetx/includes/legacy-hero.html.twig */
class __TwigTemplate_0bb95399d2eb9eeb5e7331443489de0e extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 2
        $context["asset_base"] = (($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)) . "/images/");
        // line 3
        if ((($tmp = ($context["dyne_is_home"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 4
            yield "  <section class=\"hero hero-media dyne-home-hero\" aria-label=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Expanding what is possible"));
            yield "\">
    <picture class=\"dyne-home-hero-still\">
      <source media=\"(orientation: landscape)\" srcset=\"";
            // line 6
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["asset_base"] ?? null), "html", null, true);
            yield "hero/hero-home-desktop-lastframe.webp\" />
      <img src=\"";
            // line 7
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["asset_base"] ?? null), "html", null, true);
            yield "hero/hero-home-mobile-lastframe.webp\" alt=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Dyne Therapeutics: expanding what is possible for people living with neuromuscular diseases"));
            yield "\" fetchpriority=\"high\" />
    </picture>
    <video class=\"hero-video\" data-dyne-home-video muted playsinline preload=\"none\" aria-hidden=\"true\"
      data-desktop-src=\"";
            // line 10
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["asset_base"] ?? null), "html", null, true);
            yield "hero/hero-home-desktop.mp4\"
      data-mobile-src=\"";
            // line 11
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["asset_base"] ?? null), "html", null, true);
            yield "hero/hero-home-mobile.mp4\"
      data-desktop-poster=\"";
            // line 12
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["asset_base"] ?? null), "html", null, true);
            yield "hero/hero-home-desktop.webp\"
      data-mobile-poster=\"";
            // line 13
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["asset_base"] ?? null), "html", null, true);
            yield "hero/hero-home-mobile.webp\"></video>
    <button class=\"dyne-hero-motion\" type=\"button\" data-dyne-hero-motion hidden>";
            // line 14
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Pause animation"));
            yield "</button>
  </section>
";
        } elseif ((CoreExtension::getAttribute($this->env, $this->source,         // line 16
($context["dyne_hero"] ?? null), "classes", [], "any", false, false, true, 16) == "perspectives")) {
            // line 17
            yield "  <div class=\"hero perspectives dyne-perspectives-hero\">
    <div class=\"hero-title-2\">";
            // line 18
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("perspectives"));
            yield "</div>
    <div class=\"hero-subtitle\">";
            // line 19
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("We are passionate about using our voices to advance our mission of developing life-transforming therapies for people living with serious muscle diseases."));
            yield "</div>
  </div>
";
        } elseif ((($tmp = CoreExtension::getAttribute($this->env, $this->source,         // line 21
($context["dyne_hero"] ?? null), "third_level", [], "any", false, false, true, 21)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 22
            yield "  <div class=\"hero hero-third-level\" aria-hidden=\"true\"></div>
";
        } elseif ((($tmp = CoreExtension::getAttribute($this->env, $this->source,         // line 23
($context["dyne_hero"] ?? null), "desktop", [], "any", false, false, true, 23)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 24
            yield "  <div class=\"hero hero-secondary ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ((CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "classes", [], "any", true, true, true, 24)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "classes", [], "any", false, false, true, 24), "")) : ("")), "html", null, true);
            yield "\">
    <div class=\"hero-bg-base bg-img\">
      <picture>
        ";
            // line 27
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "mobile", [], "any", false, false, true, 27)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<source srcset=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "mobile", [], "any", false, false, true, 27), "html", null, true);
                yield "\" media=\"(max-width: 767.98px)\" />";
            }
            // line 28
            yield "        <img src=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "desktop", [], "any", false, false, true, 28), "html", null, true);
            yield "\" alt=\"\" fetchpriority=\"high\" />
      </picture>
    </div>
    ";
            // line 31
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "line1", [], "any", false, false, true, 31)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<p class=\"h1-secondary-1\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::striptags(CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "line1", [], "any", false, false, true, 31)), "html", null, true);
                yield "</p>";
            }
            // line 32
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "overlay", [], "any", false, false, true, 32)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<div class=\"hero-bg-overlay bg-img\"><img src=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "overlay", [], "any", false, false, true, 32), "html", null, true);
                yield "\" alt=\"\" /></div>";
            }
            // line 33
            yield "    ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "line2", [], "any", false, false, true, 33)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<p class=\"h1-secondary-2\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::striptags(CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_hero"] ?? null), "line2", [], "any", false, false, true, 33)), "html", null, true);
                yield "</p>";
            }
            // line 34
            yield "  </div>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["directory", "dyne_is_home", "dyne_hero"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@dynetx/includes/legacy-hero.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  148 => 34,  141 => 33,  134 => 32,  128 => 31,  121 => 28,  115 => 27,  108 => 24,  106 => 23,  103 => 22,  101 => 21,  96 => 19,  92 => 18,  89 => 17,  87 => 16,  82 => 14,  78 => 13,  74 => 12,  70 => 11,  66 => 10,  58 => 7,  54 => 6,  48 => 4,  46 => 3,  44 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@dynetx/includes/legacy-hero.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/includes/legacy-hero.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 2, "if" => 3];
        static $filters = ["t" => 4, "escape" => 6, "default" => 24, "striptags" => 31];
        static $functions = ["path" => 2];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "if"],
                [0 => "t", 1 => "escape", 2 => "default", 3 => "striptags"],
                [0 => "path"],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            } elseif ($e instanceof SecurityNotAllowedTestError && isset($tests[$e->getTestName()])) {
                $e->setTemplateLine($tests[$e->getTestName()]);
            }

            throw $e;
        }

    }
}
