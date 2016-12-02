# moveSomeAnswers
When using randomize order in answers, you can want to get some specific answers at last position, this plugin offer a solution.

## Documentation
Before activate this plugin you need to get and activate [toolsSmartDomDocument plugin](https://framagit.org/SondagePro-LimeSurvey-plugin/toolsDomDocument).

After you can set specific code to be always at end (if you use random_order attribute in a question).

You have 3 settings:
- Default globally: managed at plugin settings ; set it to empty or dot (.) to deactivate by default
- Default by survey : each survey can have own default: set it to dot (.) to deactivate by survey ; to use global default use an empty string
- New question attribute : if question have random_order attribute, the new attribute is tested (get default from survey if is empty or not set). The list of answer code is put at end if exist. The answer code can be separate by comma (,).

## Copyright
- Copyright © 2016 Denis Chenu <http://sondages.pro>
- Licence : GNU General Public License <https://www.gnu.org/licenses/gpl-3.0.html>
