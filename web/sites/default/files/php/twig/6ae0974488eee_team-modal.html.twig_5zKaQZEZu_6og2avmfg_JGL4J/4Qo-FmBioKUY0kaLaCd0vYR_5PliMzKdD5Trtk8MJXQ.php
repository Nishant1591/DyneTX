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

/* @dynetx/includes/team-modal.html.twig */
class __TwigTemplate_e6a56af1691b0872ae9effcb965d972a extends Template
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
        yield "<template id=\"dyne-team-dialog-template\">
  <div class=\"dyne-team\">
    <div class=\"modal fade\" id=\"modalTeam\" tabindex=\"-1\" aria-labelledby=\"modalTeamTitle\" aria-hidden=\"true\">
      <div class=\"modal-dialog modal-dialog-centered modal-xl\">
        <div class=\"modal-content\">
          <div class=\"modal-body\">
            <div class=\"row\" data-dyne-team-content></div>
            <button type=\"button\" class=\"btn close\" data-bs-dismiss=\"modal\" aria-label=\"";
        // line 9
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Close biography"));
        yield "\"><i aria-hidden=\"true\"></i></button>
          </div>
          <div class=\"modal-footer\">
            <button type=\"button\" class=\"btn btn-primary btn-arrow-left\" data-dyne-team-direction=\"prev\" aria-label=\"";
        // line 12
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Previous bio"));
        yield "\"></button>
            <button type=\"button\" class=\"btn btn-primary btn-arrow-right\" data-dyne-team-direction=\"next\" aria-label=\"";
        // line 13
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Next bio"));
        yield "\"></button>
          </div>
          <span class=\"visually-hidden\" role=\"status\" data-dyne-team-announcement></span>
        </div>
      </div>
    </div>
  </div>
</template>
";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@dynetx/includes/team-modal.html.twig";
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
        return array (  63 => 13,  59 => 12,  53 => 9,  44 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@dynetx/includes/team-modal.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/includes/team-modal.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = [];
        static $filters = ["t" => 9];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [],
                [0 => "t"],
                [],
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
