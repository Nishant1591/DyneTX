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

/* themes/custom/dynetx/templates/page.html.twig */
class __TwigTemplate_f3dd1bd9421e27c9c34e12a379a56d32 extends Template
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
        yield "
";
        // line 3
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "alert_banner", [], "any", false, false, true, 3)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 4
            yield "  <div class=\"duchenne-alert-bar\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "alert_banner", [], "any", false, false, true, 4), "html", null, true);
            yield "</div>
";
        }
        // line 6
        yield "
<header class=\"dyne-header header version-a fixed-top\" role=\"banner\">
  <a href=\"";
        // line 8
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
        yield "\" class=\"logo ms-lg-3 mx-xl-0\" aria-label=\"";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Dyne home"));
        yield "\">
    <img src=\"";
        // line 9
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
        yield "/images/logo-shape.svg\" width=\"176\" height=\"50\" class=\"img-fluid\" alt=\"";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Dyne Therapeutics"));
        yield "\" />
  </a>
  <nav class=\"dyne-nav navbar navbar-expand-lg d-none d-lg-flex\" aria-label=\"";
        // line 11
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Primary navigation"));
        yield "\">
    ";
        // line 12
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "primary_menu", [], "any", false, false, true, 12), "html", null, true);
        yield "
  </nav>
  <button class=\"navbar-toggler dyne-drawer-toggle button-collapse d-lg-none ms-auto me-lg-3\" type=\"button\" data-bs-toggle=\"offcanvas\" data-bs-target=\"#dyneMobileDrawer\" aria-controls=\"dyneMobileDrawer\" aria-label=\"";
        // line 14
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Open navigation"));
        yield "\">
    <i aria-hidden=\"true\"></i>
  </button>
  ";
        // line 17
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header", [], "any", false, false, true, 17), "html", null, true);
        yield "
</header>
<div class=\"offcanvas offcanvas-end dyne-mobile-drawer\" id=\"dyneMobileDrawer\" tabindex=\"-1\" aria-labelledby=\"dyneMobileDrawerLabel\" data-bs-backdrop=\"false\">
  <div class=\"offcanvas-header\">
    <a class=\"dyne-drawer-logo\" href=\"";
        // line 21
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
        yield "\"><img src=\"";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
        yield "/images/logo-shape.svg\" width=\"176\" height=\"50\" alt=\"";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Dyne Therapeutics home"));
        yield "\" /></a>
    <h2 id=\"dyneMobileDrawerLabel\" class=\"visually-hidden\">";
        // line 22
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Main menu"));
        yield "</h2>
    <button type=\"button\" class=\"dyne-drawer-close\" data-bs-dismiss=\"offcanvas\" aria-label=\"";
        // line 23
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Close navigation"));
        yield "\"></button>
  </div>
  <div class=\"offcanvas-body\">
    ";
        // line 26
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "mobile_drawer", [], "any", false, false, true, 26), "html", null, true);
        yield "
    <nav class=\"dyne-mobile-nav\" aria-label=\"";
        // line 27
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Mobile primary navigation"));
        yield "\">";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "primary_menu", [], "any", false, false, true, 27), "html", null, true);
        yield "</nav>
    <div class=\"dyne-mobile-contact\">
      <h3>";
        // line 29
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Stay in Touch"));
        yield "</h3>
      <a href=\"mailto:info@dyne-tx.com\">info@dyne-tx.com</a>
      <a href=\"tel:7817868230\">781.786.8230</a>
      <div class=\"dyne-drawer-socials\">
        <a href=\"https://www.linkedin.com/company/dynetx\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"LinkedIn\"><i class=\"ico-in ico-xs\" aria-hidden=\"true\"></i></a>
        <a href=\"https://twitter.com/dyne_tx\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"X\"><i class=\"ico-tw ico-xs\" aria-hidden=\"true\"></i></a>
        <a href=\"https://www.facebook.com/DyneTherapeutic/\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"Facebook\"><i class=\"ico-fb ico-xs\" aria-hidden=\"true\"></i></a>
        <a href=\"https://www.youtube.com/@dynetherapeutics\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"YouTube\"><i class=\"ico-youtube ico-xs\" aria-hidden=\"true\"></i></a>
      </div>
    </div>
  </div>
</div>

";
        // line 42
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "breadcrumb", [], "any", false, false, true, 42), "html", null, true);
        yield "
";
        // line 43
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "highlighted", [], "any", false, false, true, 43), "html", null, true);
        yield "
<main id=\"main-content\" class=\"dyne-main\" tabindex=\"-1\">
  ";
        // line 45
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "content", [], "any", false, false, true, 45), "html", null, true);
        yield "
</main>

<footer class=\"site-footer\" role=\"contentinfo\">
  <div class=\"footer-blend\"></div>
  <div class=\"container\">
    <div class=\"row\">
      <div class=\"col-12 col-lg-10 footer-main\">
        <div class=\"row footer-info\">
          <div class=\"col-12 col-lg-3 footer-info-item\"><p class=\"footer-moto\">";
        // line 54
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Stay in touch"));
        yield "</p></div>
          <div class=\"col-12 col-lg-3 footer-info-item\"><a class=\"footer-contact\" href=\"mailto:info@dyne-tx.com\">info@dyne-tx.com</a></div>
          <div class=\"col-12 col-lg-auto footer-info-item\"><a class=\"footer-contact\" href=\"tel:7817868230\">781.786.8230</a></div>
          <div class=\"col-12 col-lg-auto ms-lg-auto footer-info-item footer-socials-col\">
            <div class=\"footer-socials\">
              <a href=\"https://www.linkedin.com/company/dynetx\" target=\"_blank\" rel=\"noopener noreferrer\" title=\"";
        // line 59
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("LinkedIn (opens in a new tab)"));
        yield "\"><img src=\"";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
        yield "/images/footer/social-linkedin.svg\" width=\"32\" height=\"32\" alt=\"LinkedIn\" /></a>
              <a href=\"https://twitter.com/dyne_tx\" target=\"_blank\" rel=\"noopener noreferrer\" title=\"";
        // line 60
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("X (opens in a new tab)"));
        yield "\"><img src=\"";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
        yield "/images/footer/social-x.svg\" width=\"32\" height=\"32\" alt=\"X\" /></a>
              <a href=\"https://www.facebook.com/DyneTherapeutic/\" target=\"_blank\" rel=\"noopener noreferrer\" title=\"";
        // line 61
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Facebook (opens in a new tab)"));
        yield "\"><img src=\"";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
        yield "/images/footer/social-facebook.svg\" width=\"32\" height=\"32\" alt=\"Facebook\" /></a>
              <a href=\"https://www.youtube.com/@dynetherapeutics\" target=\"_blank\" rel=\"noopener noreferrer\" title=\"";
        // line 62
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("YouTube (opens in a new tab)"));
        yield "\"><img src=\"";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
        yield "/images/footer/social-youtube.svg\" width=\"32\" height=\"32\" alt=\"YouTube\" /></a>
            </div>
          </div>
        </div>
        ";
        // line 66
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "stay_in_touch", [], "any", false, false, true, 66), "html", null, true);
        yield "
        <div class=\"row footer-legal\">
          <div class=\"col-12 offset-lg-3 col-lg-auto footer-legal-item footer-legal-links\">";
        // line 68
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer", [], "any", false, false, true, 68), "html", null, true);
        yield "</div>
          <div class=\"col-12 col-lg-auto ms-lg-auto footer-legal-item footer-copyright-col\"><span>&copy; ";
        // line 69
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Twig\Extension\CoreExtension']->formatDate("now", "Y"), "html", null, true);
        yield " Dyne Therapeutics, Inc.</span></div>
        </div>
      </div>
      <div class=\"col-lg-2 footer-mark-col\"><img src=\"";
        // line 72
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
        yield "/images/footer/dyne-mark.svg\" alt=\"\" class=\"footer-mark\" /></div>
    </div>
  </div>
</footer>

<div class=\"modal fade\" id=\"dyneExitModal\" tabindex=\"-1\" aria-labelledby=\"dyneExitModalTitle\" aria-hidden=\"true\">
  <div class=\"modal-dialog modal-dialog-centered modal-lg\">
    <div class=\"modal-content\">
      <div class=\"modal-body py-5\">
        <button type=\"button\" class=\"btn close\" data-bs-dismiss=\"modal\" aria-label=\"";
        // line 81
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Close"));
        yield "\"><i aria-hidden=\"true\"></i></button>
        <div class=\"row justify-content-center mt-5 mb-4\">
          <div class=\"col-lg-8\">
            <h2 class=\"h4 text-center\" id=\"dyneExitModalTitle\">";
        // line 84
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("You are now leaving"));
        yield "<br />";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("the Dyne Therapeutics corporate website"));
        yield "</h2>
            <div class=\"btn-line d-flex justify-content-center mt-5\">
              <a href=\"#\" id=\"dyneExitContinue\" class=\"btn btn-primary btn-lg\" target=\"_blank\" rel=\"noopener noreferrer\">";
        // line 86
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Continue"));
        yield "</a>
              <button type=\"button\" class=\"btn btn-secondary btn-lg\" data-bs-dismiss=\"modal\">";
        // line 87
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Stay on page"));
        yield "</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
";
        // line 95
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "modals", [], "any", false, false, true, 95), "html", null, true);
        yield "
";
        // line 96
        yield from $this->load("@dynetx/includes/team-modal.html.twig", 96)->unwrap()->yield($context);
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page", "directory"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/dynetx/templates/page.html.twig";
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
        return array (  254 => 96,  250 => 95,  239 => 87,  235 => 86,  228 => 84,  222 => 81,  210 => 72,  204 => 69,  200 => 68,  195 => 66,  186 => 62,  180 => 61,  174 => 60,  168 => 59,  160 => 54,  148 => 45,  143 => 43,  139 => 42,  123 => 29,  116 => 27,  112 => 26,  106 => 23,  102 => 22,  94 => 21,  87 => 17,  81 => 14,  76 => 12,  72 => 11,  65 => 9,  59 => 8,  55 => 6,  49 => 4,  47 => 3,  44 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/dynetx/templates/page.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/page.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 3, "include" => 96];
        static $filters = ["escape" => 4, "t" => 8, "date" => 69];
        static $functions = ["path" => 8];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if", 1 => "include"],
                [0 => "escape", 1 => "t", 2 => "date"],
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
