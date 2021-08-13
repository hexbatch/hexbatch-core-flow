<?php
namespace app\models\project;
/*
 * git --no-pager log -n30000 --pretty=format:'{%n  "commit": "%H",%n  "abbreviated_commit": "%h",%n  "tree": "%T",%n  "abbreviated_tree": "%t",%n  "parent": "%P",%n  "abbreviated_parent": "%p",%n  "refs": "%D",%n  "encoding": "%e",%n  "subject": "%s",%n  "sanitized_subject_line": "%f",%n  "body": "%b",%n  "commit_notes": "%N",%n  "verification_flag": "%G?",%n  "signer": "%GS",%n  "signer_key": "%GK",%n  "author": {%n    "name": "%aN",%n    "email": "%aE",%n    "date": "%at"%n  },%n  "commiter": {%n    "name": "%cN",%n    "email": "%cE",%n    "date": "%ct"%n  }%n},'
 *
 * without newlines or trailing comma, and different quotes
 * git --no-pager log -n30000 --pretty=format:'{   ||qq|| commit ||qq|| :  ||qq|| %H ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| %h ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| %T ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| %t ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| %P ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| %p ||qq|| ,   ||qq|| refs ||qq|| :  ||qq|| %D ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq|| %e ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| %s ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| %f ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| %b ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq|| %N ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| %G? ||qq|| ,   ||qq|| signer ||qq|| :  ||qq|| %GS ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq|| %GK ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| %aN ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| %aE ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| %at ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| %cN ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| %cE ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| %ct ||qq||   }}::end::' |  tr '\n' '||nn||' | tr '"' '\\"'
 *
 */

/*
{   ||qq|| commit ||qq|| :  ||qq|| 205c5add57482498a915abf764cce981814f61f0 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| 205c5ad ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 227c371795a7fda9bbd8f7f91bfe83f4af969fe4 ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 227c371 ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| 3fb9d53f4e20119daa7e3750e117113a8967e46b ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| 3fb9d53 ||qq|| ,   ||qq|| refs ||qq|| :  ||qq|| HEAD -> master ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| 11EBE1D3BC0292BFB6380242AC130005 ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| willwoodlief+help@gmail.com ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797270 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797278 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| 3fb9d53f4e20119daa7e3750e117113a8967e46b ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| 3fb9d53 ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 85c177adc9a816f0fd42f46394eef23e487b89b6 ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 85c177a ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| ee8e29e4cf71e1826198bcbe1d545ff5feff0166 ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| ee8e29e ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797203 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797203 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| ee8e29e4cf71e1826198bcbe1d545ff5feff0166 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| ee8e29e ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| eaac2ae3ceabcfcfae93174f668604160bc8ec17 ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| eaac2ae ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| d8d07ec002f263dac4f68fe1c5d611cfe7d0cf28 ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| d8d07ec ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797162 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797162 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| d8d07ec002f263dac4f68fe1c5d611cfe7d0cf28 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| d8d07ec ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| b9d84fdcd1a064642a1d3f8dad243a25894b0020 ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| b9d84fd ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| bb8e0c3de357112ea3e441cab404004dad34c206 ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| bb8e0c3 ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628796860 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628796860 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| bb8e0c3de357112ea3e441cab404004dad34c206 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| bb8e0c3 ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 89ad5401fdb869b8ffee865ff85f7bbc8ae89eaa ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 89ad540 ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| 6ca0fb1f74cd197226af0703f6ad3fbc019f7d7b ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| 6ca0fb1 ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628796572 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628796572 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| 6ca0fb1f74cd197226af0703f6ad3fbc019f7d7b ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| 6ca0fb1 ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 3677a4ebda8625a5759dcd6070d0a758ceece1dc ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 3677a4e ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| 2f7f44fcb08e7c6fdce4d1031892a406554901a5 ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| 2f7f44f ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Auto Commit ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Auto-Commit ||qq|| ,   ||qq|| body ||qq|| :  ||qq||  ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628698994 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628698994 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| 2f7f44fcb08e7c6fdce4d1031892a406554901a5 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| 2f7f44f ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 79a92c8b83832a1a557f8c7d5adeaa0644c0f27a ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 79a92c8 ||qq|| ,   ||qq|| parent ||qq|| :  ||qq||  ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq||  ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Auto Commit ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Auto-Commit ||qq|| ,   ||qq|| body ||qq|| :  ||qq||  ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628698713 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628698713 ||qq||   }}::end::
*/

//example output
/*
{
  "commit": "205c5add57482498a915abf764cce981814f61f0",
  "abbreviated_commit": "205c5ad",
  "tree": "227c371795a7fda9bbd8f7f91bfe83f4af969fe4",
  "abbreviated_tree": "227c371",
  "parent": "3fb9d53f4e20119daa7e3750e117113a8967e46b",
  "abbreviated_parent": "3fb9d53",
  "refs": "HEAD -> master",
  "encoding": "",
  "subject": "Changed Project",
  "sanitized_subject_line": "Changed-Project",
  "body": "Updated Project Blurb",
  "commit_notes": "",
  "verification_flag": "N",
  "signer": "",
  "signer_key": "",
  "author": {
    "name": "11EBE1D3BC0292BFB6380242AC130005",
    "email": "willwoodlief+help@gmail.com",
    "date": "1628797270"
  },
  "commiter": {
    "name": "www-data",
    "email": "www-data@048adf2db077",
    "date": "1628797278"
  }
}

 */

/*
 * git --no-pager log --stat -n30000 --no-color
 */

/*
commit 205c5add57482498a915abf764cce981814f61f0 (HEAD -> master)
Author: 11EBE1D3BC0292BFB6380242AC130005 <willwoodlief+help@gmail.com>
Date:   Thu Aug 12 19:41:10 2021 +0000

    Changed Project

    Updated Project Blurb

 flow_project.yaml      | 2 +-
 flow_project_blurb.txt | 2 +-
 2 files changed, 2 insertions(+), 2 deletions(-)
(blank line at end of commit)
 */


/*
 * tags
 * git show-ref --tags -d
 * //the line ending with the ^{} is the commit hash for annotated tags, light tags only have the one line for commit; discard dupliate tagname with no {}
205c5add57482498a915abf764cce981814f61f0 refs/tags/BBB
ee9620b8189e9efc59c665afab141a0a9f245c4d refs/tags/v1.4
205c5add57482498a915abf764cce981814f61f0 refs/tags/v1.4^{}

extra
19:38 $ git for-each-ref refs/tags
205c5add57482498a915abf764cce981814f61f0 commit	refs/tags/BBB
ee9620b8189e9efc59c665afab141a0a9f245c4d tag	refs/tags/v1.4

 */

class FlowGitHistory {

    public ?string $project_guid;
    public ?string $commit;
    public ?string $abbreviated_commit;
    public ?string $tree;
    public ?string $abbreviated_tree;
    public ?string $parent;
    public ?string $abbreviated_parent;
    public ?string $refs;
    public ?string $encoding;
    public ?string $subject;
    public ?string $body;
    public ?string $author_guid;
    public ?string $author_email;
    public ?string $commit_ts;

    /**
     * @var string $file_paths
     */
    public array $file_paths= [];

    /**
     * @var string[] $tags
     */
    public array $tags = [];

    /**
     * @param string $project_path
     * @return FlowGitHistory[]
     */
    public static function get_history(string $project_path) :array {

        return [];
    }

}